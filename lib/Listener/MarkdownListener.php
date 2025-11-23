<?php
declare(strict_types=1);

namespace OCA\CRM\Listener;

use OCP\EventDispatcher\Event;
use OCP\Files\Events\Node\NodeWrittenEvent;
use Psr\Log\LoggerInterface;
use OCP\Files\IRootFolder;
use OCP\Files\File;
use OCP\Contacts\IManager as ContactsManager;
use OCP\IUserSession;
use OCP\IConfig;
use OCA\DAV\CardDAV\CardDavBackend;
use Sabre\VObject\Component\VCard;
use OCA\DAV\CalDAV\CalDavBackend;

class MarkdownListener {
    private LoggerInterface $logger;
    private IRootFolder $rootFolder;
    private ContactsManager $contactsManager;
    private IUserSession $userSession;
    private IConfig $config;

    public function __construct(
        LoggerInterface $logger,
        IRootFolder $rootFolder,
        IUserSession $userSession,
        IConfig $config
    ) {
        $this->logger = $logger;
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->config = $config;
    }

    public function handle(Event $event): void {
        $this->logger->info('MarkdownListener déclenché.');

        $node = $event->getNode();
        if (!$node instanceof File) return;

        $this->logger->info("Fichier écrit : " . $node->getPath());

        if ($node->getMimetype() !== 'text/markdown') return;

        try {
            $stream = $node->fopen('r');
            $text = stream_get_contents($stream);

            $metadata = $this->parseMdMetadata($text);
            $this->logger->info("Metadata extraits: " . json_encode($metadata));

            if (!empty($metadata['Classe']) && $metadata['Classe'] === 'Personne') {
                $this->addContact($node->getName(), $metadata);
            }

            if (!empty($metadata['Classe']) && $metadata['Classe'] === 'Action') {
                $this->addAction($node->getName(), $metadata, $text);
            }

            $this->logger->info("Markdown processed: " . $node->getName());
        } catch (\Exception $e) {
            $this->logger->error("Erreur traitement Markdown: " . $e->getMessage());
        }
    }

    private function parseMdMetadata(string $content): array {
        if (preg_match('/^---(.*?)---/s', $content, $matches)) {
            $lines = explode("\n", trim($matches[1]));
            $data = [];
            foreach ($lines as $line) {
                if (strpos($line, ':') !== false) {
                    [$key, $value] = explode(':', $line, 2);
                    $data[trim($key)] = trim($value);
                }
            }
            return $data;
        }
        return [];
    }




private function addAction(string $name, array $metadata, string $text): void {
    $logger = $this->logger;
    $user = $this->userSession->getUser();
    if (!$user) {
        $logger->error("Aucun utilisateur connecté.");
        return;
    }
    $userId = $user->getUID();

    try {
        /** @var CalDavBackend $calDavBackend */
        $calDavBackend = \OC::$server->query(CalDavBackend::class);

        // Récupérer tous les calendriers de l’utilisateur
        $calendars = $calDavBackend->getCalendarsForUser("principals/users/$userId");
        if (empty($calendars)) {
            $logger->error("Aucun calendrier trouvé pour l’utilisateur $userId");
            return;
        }

        // Prendre le premier (souvent "personal")
        $calendar = $calendars[0];

        // Nettoyer / préparer les données
        $actionName = preg_replace('/\.md$/', '', $name ?? 'Sans nom');
        $rawDate = trim($metadata['Date'] ?? date('Y-m-d H:i:s'), "'");
        $start = new \DateTime($rawDate . ' 00:00:00');
        $end = (clone $start)->modify('+1 hour');

        $vcal = "BEGIN:VCALENDAR\r\n";
        $vcal .= "VERSION:2.0\r\n";
        $vcal .= "PRODID:-//Nextcloud CRM Plugin//EN\r\n";
        $vcal .= "CALSCALE:GREGORIAN\r\n";
        $vcal .= "BEGIN:VEVENT\r\n";
        $vcal .= "UID:" . uniqid() . "@nextcloud\r\n";

        // Date/heure actuelle pour DTSTAMP
        $vcal .= "DTSTAMP:" . (new \DateTime('now', new \DateTimeZone('UTC')))->format('Ymd\THis\Z') . "\r\n";

        $vcal .= "SUMMARY:" . $actionName . "\r\n";
        $vcal .= "DESCRIPTION:" . "test" . "\r\n";
        $vcal .= "DTSTART:" . $start->format('Ymd\THis\Z') . "\r\n";
        $vcal .= "DTEND:" . $end->format('Ymd\THis\Z') . "\r\n";
        $vcal .= "END:VEVENT\r\n";
        $vcal .= "END:VCALENDAR\r\n";

        $logger->info("Détails de l'action :  $vcal");
        // Nom ICS unique
        $filename = uniqid('event_', true) . '.ics';

        // Création via le backend → met à jour DB + fichier
        $calDavBackend->createCalendarObject($calendar['id'], $filename, $vcal);

        $logger->info("Nouvelle action ajoutée dans le calendrier {$calendar['uri']} pour $userId : $filename");
        

    } catch (\Exception $e) {
        $logger->error("Erreur ajout action dans calendrier interne : " . $e->getMessage());
    }
}



private function addContact(string $name, array $metadata): void {
    $backend = \OC::$server->get(CardDavBackend::class);
    $user = $this->userSession->getUser();
    if (!$user) {
        $this->logger->error("Aucun utilisateur connecté.");
        return;
    }
    $userId = $user->getUID();

    // 1. Récupérer les carnets de l’utilisateur
    $addressBooks = $backend->getAddressBooksForUser("principals/users/$userId");

    if (empty($addressBooks)) {
        $this->logger->error("Aucun carnet trouvé pour $userId");
        return;
    }

    // 2. Choisir le carnet par défaut (souvent 'contacts')
    $addressBookId = null;
    foreach ($addressBooks as $ab) {
        if ($ab['uri'] === 'contacts' || $ab['uri'] === 'default') {
            $addressBookId = $ab['id'];
            break;
        }
    }

    // Si rien trouvé, prendre le premier
    if ($addressBookId === null) {
        $addressBookId = $addressBooks[0]['id'];
    }

    $contactId = $metadata['Id'] . '.vcf';

    $vcard = new VCard();
    $contactName = preg_replace('/\.md$/', '', $name ?? 'Sans nom');
    $vcard->add('FN', $contactName);
    if (!empty($metadata['Email'])) {
        $vcard->add('EMAIL', $metadata['Email']);
    }
    if (!empty($metadata['Téléphone'])) {
        $vcard->add('TEL', $metadata['Téléphone']);
    }
    if (!empty($metadata['Portable'])) {
        $vcard->add('TEL', $metadata['Portable'])->add('TYPE', 'cell');
    }

    // Vérifier si le contact existe déjà
    $existingCard = null;
    foreach ($backend->getCards($addressBookId) as $card) {
        if ($card['uri'] === $contactId) {
            $existingCard = $card;
            break;
        }
    }

    if ($existingCard) {
        $backend->updateCard($addressBookId, $contactId, $vcard->serialize());
        $this->logger->info("Contact mis à jour dans le carnet de $userId");
    } else {
        $backend->createCard($addressBookId, $contactId, $vcard->serialize());
        $this->logger->info("Contact ajouté directement au carnet de $userId");
    }
}

}
