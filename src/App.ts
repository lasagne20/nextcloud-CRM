import { Vault, File } from 'markdown-crm';
import * as yaml from 'js-yaml';
import type { Settings } from 'markdown-crm/src/vault/Vault';
import type { IApp, IFile, IFolder, ISettings } from 'markdown-crm/src/interfaces/IApp';

/**
 * NextcloudApp - Adaptateur pour intégrer Markdown-CRM avec Nextcloud
 * Implémente l'interface IApp pour permettre au système Markdown-CRM
 * de gérer les métadonnées des fichiers .md dans Nextcloud
 */
export class NextcloudApp implements IApp {
    private baseUrl: string;
    private settings: ISettings;
    private metadataCache: Map<string, { metadata: any, timestamp: number }> = new Map();
    private readonly CACHE_TTL = 5000; // 5 secondes de cache

    constructor(baseUrl: string = '/apps/crm') {
        this.baseUrl = baseUrl;
        this.settings = {
            phoneFormat: 'FR',
            dateFormat: 'DD/MM/YYYY',
            timeFormat: '24h',
            timezone: 'Europe/Paris',
            numberLocale: 'fr-FR',
            currencySymbol: '€'
        };
    }

    // Settings
    getSettings(): ISettings {
        return this.settings;
    }

    // File operations
    async readFile(file: IFile): Promise<string> {
        // Si c'est un fichier YAML (detecter Config ou config dans le path)
        if (file.extension === 'yaml' && (file.path.includes('/Config/') || file.path.includes('/config/'))) {
            const configName = file.basename || file.name.replace('.yaml', '');
            const response = await fetch(`${this.baseUrl}/config/${encodeURIComponent(configName)}`);
            if (!response.ok) {
                throw new Error(`Failed to read config: ${file.path}`);
            }
            const data = await response.json();
            return data.content || '';
        }
        
        // Sinon, c'est un fichier .md
        const fileName = file.name || file.path.split('/').pop() || 'unknown.md';
        const response = await fetch(`${this.baseUrl}/files/md/${encodeURIComponent(fileName)}`);
        if (!response.ok) {
            throw new Error(`Failed to read file: ${file.path}`);
        }
        const data = await response.json();
        return data.content || '';
    }

    async writeFile(file: IFile, content: string): Promise<void> {
        const response = await fetch(`${this.baseUrl}/files/md/save`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                path: file.path,
                content: content
            })
        });
        if (!response.ok) {
            throw new Error(`Failed to write file: ${file.path}`);
        }
    }

    async createFile(path: string, content: string): Promise<IFile> {
        await this.writeFile({ path, name: path.split('/').pop() || '', basename: '', extension: 'md' }, content);
        return this.getFile(path) as Promise<IFile>;
    }

    async delete(file: IFile | IFolder): Promise<void> {
        const response = await fetch(`${this.baseUrl}/file?path=${encodeURIComponent(file.path)}`, {
            method: 'DELETE'
        });
        if (!response.ok) {
            throw new Error(`Failed to delete: ${file.path}`);
        }
    }

    async move(fileOrFolder: IFile | IFolder, newPath: string): Promise<void> {
        const response = await fetch(`${this.baseUrl}/file/move`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                from: fileOrFolder.path,
                to: newPath
            })
        });
        if (!response.ok) {
            throw new Error(`Failed to move: ${fileOrFolder.path}`);
        }
    }

    // Folder operations
    async createFolder(path: string): Promise<IFolder> {
        const response = await fetch(`${this.baseUrl}/folder`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ path })
        });
        if (!response.ok) {
            throw new Error(`Failed to create folder: ${path}`);
        }
        return {
            path,
            name: path.split('/').pop() || '',
            children: []
        };
    }

    async listFiles(folder?: IFolder): Promise<IFile[]> {
        const results: IFile[] = [];
        
        console.log('📂 listFiles called with folder:', folder);
        
        // Récupérer les fichiers .md
        console.log('📂 Requesting MD files from:', `${this.baseUrl}/files/md`);
        try {
            const mdResponse = await fetch(`${this.baseUrl}/files/md`);
            console.log('📂 MD Response status:', mdResponse.status, mdResponse.statusText);
            
            if (mdResponse.ok) {
                const mdData = await mdResponse.json();
                const mdFiles = mdData.map((f: any) => ({
                    path: f.path,
                    name: f.name,
                    basename: f.name.replace(/\.md$/, ''),
                    extension: 'md'
                }));
                results.push(...mdFiles);
            }
        } catch (error) {
            console.error('❌ Failed to list MD files:', error);
        }
        
        // Récupérer les fichiers YAML de config
        try {
            const configResponse = await fetch(`${this.baseUrl}/config/list`);
            
            if (configResponse.ok) {
                const configData = await configResponse.json();
                const yamlFiles = configData.map((f: any) => ({
                    path: f.path,
                    name: f.file,
                    basename: f.name,
                    extension: 'yaml'
                }));
                results.push(...yamlFiles);
            }
        } catch (error) {
            console.error('Failed to list config files:', error);
        }
        
        return results;
    }

    async listFolders(folder?: IFolder): Promise<IFolder[]> {
        // Pour l'instant, retourne un tableau vide
        // À implémenter selon les besoins
        return [];
    }

    async getFile(path: string): Promise<IFile | null> {
        try {
            const fileName = path.split('/').pop() || '';
            const extension = fileName.split('.').pop() || '';
            
            // Si c'est un fichier YAML (detecter Config ou config dans le path)
            if (extension === 'yaml' && (path.includes('/Config/') || path.includes('/config/'))) {
                const configName = fileName.replace('.yaml', '');
                const response = await fetch(`${this.baseUrl}/config/${encodeURIComponent(configName)}`);
                if (!response.ok) {
                    console.warn('⚠️ Config file not found:', path);
                    return null;
                }
                // Le fichier existe
                return {
                    path: path,
                    name: fileName,
                    basename: fileName.replace(/\.yaml$/, ''),
                    extension: 'yaml'
                };
            }
            
            // Sinon, c'est un fichier .md
            const response = await fetch(`${this.baseUrl}/files/md/${encodeURIComponent(fileName)}`);
            if (!response.ok) {
                return null;
            }
            return {
                path: path,
                name: fileName,
                basename: fileName.replace(/\.md$/, ''),
                extension: extension
            };
        } catch (error) {
            console.error('❌ Error in getFile:', error);
            return null;
        }
    }

    getAbsolutePath(relativePath: string): string {
        return `${this.baseUrl}/${relativePath}`;
    }

    getName(): string {
        return 'Nextcloud CRM';
    }

    isFolder(file: IFile): boolean {
        return !file.extension || file.extension === '';
    }

    isFile(file: IFile): boolean {
        return !!file.extension;
    }

    getUrl(path: string): string {
        return `${this.baseUrl}/file?path=${encodeURIComponent(path)}`;
    }

    // Metadata operations
    async getMetadata(file: IFile): Promise<Record<string, any>> {
        const cacheKey = file.path;
        const now = Date.now();
        
        // Vérifier le cache
        const cached = this.metadataCache.get(cacheKey);
        if (cached && (now - cached.timestamp) < this.CACHE_TTL) {
            return cached.metadata;
        }
        
        try {
            const content = await this.readFile(file);
            const metadata = this.parseFrontmatter(content);
            
            // Mettre en cache
            this.metadataCache.set(cacheKey, {
                metadata,
                timestamp: now
            });
            
            return metadata;
        } catch (error) {
            console.error('Failed to get metadata:', error);
            return {};
        }
    }

    async updateMetadata(file: IFile, metadata: Record<string, any>): Promise<void> {
        try {
            const content = await this.readFile(file);
            const { body } = this.extractFrontmatter(content);
            const newContent = this.buildFileWithFrontmatter(metadata, body);
            await this.writeFile(file, newContent);
            
            // Invalider le cache
            this.metadataCache.delete(file.path);
        } catch (error) {
            console.error('Failed to update metadata:', error);
            throw error;
        }
    }

    // UI operations
    createButton(text: string, onClick: () => void): HTMLButtonElement {
        const button = document.createElement('button');
        button.textContent = text;
        button.className = 'btn';
        button.onclick = onClick;
        return button;
    }

    createInput(type: string, value?: string): HTMLInputElement {
        const input = document.createElement('input');
        input.type = type;
        if (value) {
            input.value = value;
        }
        return input;
    }

    createDiv(className?: string): HTMLDivElement {
        const div = document.createElement('div');
        if (className) {
            div.className = className;
        }
        return div;
    }

    setIcon(element: HTMLElement, iconName: string): void {
        // Mapping des icônes courantes vers des emojis ou symboles Unicode
        const iconMap: Record<string, string> = {
            'user': '👤',
            'users': '👥',
            'circle-user-round': '👤',
            'building': '🏢',
            'building-2': '🏛️',
            'map-pin': '📍',
            'file-text': '📄',
            'calendar': '📅',
            'clock': '🕐',
            'mail': '✉️',
            'email': '✉️',
            'phone': '📞',
            'smartphone': '📱',
            'link': '🔗',
            'link-2': '🔗',
            'linkedin': '🔗',
            'tag': '🏷️',
            'tags': '🏷️',
            'star': '⭐',
            'stars': '⭐',
            'check': '✓',
            'check-circle': '✅',
            'x': '✗',
            'plus': '+',
            'minus': '-',
            'edit': '✏️',
            'trash': '🗑️',
            'search': '🔍',
            'settings': '⚙️',
            'folder': '📁',
            'note': '📝',
            'notebook-pen': '📝',
            'contact': '👤',
            'location': '📍',
            'institution': '🏢',
            'action': '⚡',
            'event': '📅',
            'partnership': '🤝',
            'handshake': '🤝',
            'align-left': '📝',
            'briefcase': '💼',
            'home': '🏠',
            'house': '🏠',
            'globe': '🌐',
            'world': '🌐',
            'list': '📋',
            'target': '🎯',
            'flag': '🚩',
            'circle': '⚪',
            'square': '⬜',
            'alert-circle': '⚠️',
            'info': 'ℹ️',
            'help-circle': '❓',
            'map': '🗺️',
            'navigation': '🧭',
            'euro-sign': '€',
            'dollar-sign': '$',
            'currency': '💰'
        };
        
        const icon = iconMap[iconName] || iconMap[iconName.toLowerCase()] || '📄';
        // Remplacer complètement le contenu de l'élément avec l'emoji
        element.textContent = icon;
        element.classList.add('icon', `icon-${iconName}`);
        element.style.fontSize = '18px';
    }

    // Template operations
    async getTemplateContent(templateName: string): Promise<string> {
        // À implémenter selon le système de templates de Nextcloud
        return '';
    }

    // Settings
    getSetting(key: string): any {
        return (this.settings as any)[key];
    }

    async setSetting(key: string, value: any): Promise<void> {
        (this.settings as any)[key] = value;
    }

    getVaultPath(): string {
        return '/';
    }

    open(absoluteMediaPath: string): void {
        window.open(absoluteMediaPath, '_blank');
    }

    // Utility functions
    async waitForFileMetaDataUpdate(filePath: string, key: string, callback: () => Promise<void>): Promise<void> {
        // Attendre un court instant pour que les métadonnées soient mises à jour
        setTimeout(async () => {
            await callback();
        }, 100);
    }

    async waitForMetaDataCacheUpdate(callback: () => Promise<void>): Promise<void> {
        setTimeout(async () => {
            await callback();
        }, 100);
    }

    // Utility to select files & media
    async selectMedia(vault: any, message: string): Promise<any> {
        // À implémenter selon les besoins
        return null;
    }

    async selectFile(vault: any, classNames: string[], options: any): Promise<any> {
        // À implémenter selon les besoins
        return null;
    }

    async selectClasse(vault: any, classes: string[], prompt: string): Promise<any> {
        // À implémenter selon les besoins
        return null;
    }

    async selectMultipleFile(vault: any, classNames: string[], options: any): Promise<any[]> {
        // À implémenter selon les besoins
        return [];
    }

    sendNotice(message: string): void {
        console.log(`[Nextcloud CRM] ${message}`);
        // Utiliser le système de notifications de Nextcloud si disponible
        try {
            if (typeof window !== 'undefined' && (window as any).OC?.Notification?.showTemporary) {
                (window as any).OC.Notification.showTemporary(message);
            }
        } catch (error) {
            console.warn('OC.Notification not available:', error);
        }
    }

    // Helper methods pour le parsing du frontmatter
    private parseFrontmatter(content: string): Record<string, any> {
        console.log('🔍 Parsing frontmatter from content:', content?.substring(0, 150));
        const { frontmatter } = this.extractFrontmatter(content);
        if (!frontmatter) {
            console.warn('⚠️ No frontmatter found');
            return {};
        }

        console.log('📋 Frontmatter extracted:', frontmatter);
        
        try {
            // Utiliser js-yaml pour parser le frontmatter YAML
            const metadata = yaml.load(frontmatter) as Record<string, any>;
            console.log('✅ Frontmatter parsed successfully:', metadata);
            return metadata || {};
        } catch (error) {
            console.error('❌ Failed to parse YAML frontmatter:', error);
            // Fallback sur le parsing manuel ligne par ligne
            const metadata: Record<string, any> = {};
            const lines = frontmatter.split('\n');
            
            for (const line of lines) {
                const colonIndex = line.indexOf(':');
                if (colonIndex > 0) {
                    const key = line.substring(0, colonIndex).trim();
                    let value = line.substring(colonIndex + 1).trim();
                    
                    // Gérer les valeurs entre guillemets
                    if ((value.startsWith('"') && value.endsWith('"')) || 
                        (value.startsWith("'") && value.endsWith("'"))) {
                        value = value.substring(1, value.length - 1);
                    }
                    
                    metadata[key] = value;
                }
            }
            
            return metadata;
        }
    }

    private extractFrontmatter(content: string): { frontmatter: string | null; body: string } {
        if (!content.startsWith('---')) {
            return { frontmatter: null, body: content };
        }

        const parts = content.split('---');
        if (parts.length < 3) {
            return { frontmatter: null, body: content };
        }

        return {
            frontmatter: parts[1].trim(),
            body: parts.slice(2).join('---').trim()
        };
    }

    private buildFileWithFrontmatter(metadata: Record<string, any>, body: string): string {
        try {
            // Utiliser js-yaml pour générer du YAML valide
            const yamlContent = yaml.dump(metadata, {
                indent: 2,
                lineWidth: -1,
                noRefs: true,
                sortKeys: false
            });
            return `---\n${yamlContent}---\n${body}`;
        } catch (error) {
            console.error('❌ Failed to generate YAML frontmatter:', error);
            // Fallback sur la génération manuelle
            const frontmatterLines: string[] = [];
            
            for (const [key, value] of Object.entries(metadata)) {
                if (Array.isArray(value)) {
                    frontmatterLines.push(`${key}: [${value.map(v => `"${v}"`).join(', ')}]`);
                } else if (typeof value === 'string' && value.includes(':')) {
                    frontmatterLines.push(`${key}: "${value}"`);
                } else {
                    frontmatterLines.push(`${key}: ${value}`);
                }
            }

            return `---\n${frontmatterLines.join('\n')}\n---\n${body}`;
        }
    }
}

/**
 * Classe principale pour gérer l'intégration Markdown-CRM dans Nextcloud
 */
export class MarkdownCRMManager {
    private app: NextcloudApp;
    private vault: Vault | null = null;

    constructor(baseUrl: string = '/apps/crm') {
        this.app = new NextcloudApp(baseUrl);
    }

    /**
     * Initialise le Vault Markdown-CRM
     */
    async initialize(settings?: Partial<Settings>): Promise<Vault> {
        const defaultSettings: Settings = {
            templateFolder: 'templates',
            personalName: 'User',
            configPath: '/apps/crm/config',
            ...settings
        };

        this.vault = new Vault(this.app, defaultSettings);
        
        // Attendre que les classes dynamiques soient chargées
        const factory = this.vault.getDynamicClassFactory();
        if (factory) {
            try {
                const availableClasses = await factory.getAvailableClasses();
                
                if (availableClasses.length === 0) {
                    console.error('❌ No classes found! Check if YAML files are properly listed.');
                }
                
                for (const className of availableClasses) {
                    console.log(`📦 Loading class: ${className}`);
                    try {
                        const dynamicClass = await factory.getClass(className);
                        (this.vault.constructor as any).classes[className] = dynamicClass;
                        console.log(`✅ Class loaded: ${className}`, dynamicClass);
                    } catch (error) {
                        console.error(`❌ Failed to load class ${className}:`, error);
                    }
                }
            } catch (error) {
                console.error('❌ Failed to get available classes:', error);
            }
        } else {
            console.warn('⚠️ No DynamicClassFactory available');
        }
        
        console.log('🏗️ Vault initialized:', this.vault);
        console.log('📚 Vault.classes (static):', (this.vault.constructor as any).classes);
        console.log('🔑 Available class names:', Object.keys((this.vault.constructor as any).classes || {}));
        return this.vault;
    }

    /**
     * Récupère le Vault initialisé
     */
    getVault(): Vault | null {
        return this.vault;
    }

    /**
     * Récupère une classe par son nom
     */
    getClasse(className: string): any {
        if (!this.vault) {
            throw new Error('Vault not initialized');
        }
        console.log('🔎 Looking for class:', className);
        
        // Les classes sont stockées dans Vault.classes (propriété statique)
        const VaultConstructor = this.vault.constructor as any;
        console.log('� Vault.classes:', VaultConstructor.classes);
        console.log('📂 Available classes:', Object.keys(VaultConstructor.classes || {}));
        
        return VaultConstructor.classes?.[className];
    }

    /**
     * Récupère un fichier et ses métadonnées
     */
    async getFileWithMetadata(path: string): Promise<{ file: File; metadata: Record<string, any> } | null> {
        if (!this.vault) {
            throw new Error('Vault not initialized. Call initialize() first.');
        }

        const iFile = await this.app.getFile(path);
        if (!iFile) {
            return null;
        }

        const file = new File(this.vault, iFile);
        const metadata = await file.getMetadata();

        return { file, metadata };
    }

    /**
     * Met à jour les métadonnées d'un fichier
     */
    async updateFileMetadata(path: string, key: string, value: any): Promise<void> {
        if (!this.vault) {
            throw new Error('Vault not initialized. Call initialize() first.');
        }

        const iFile = await this.app.getFile(path);
        if (!iFile) {
            throw new Error(`File not found: ${path}`);
        }

        const file = new File(this.vault, iFile);
        await file.updateMetadata(key, value);
    }

    /**
     * Liste tous les fichiers Markdown
     */
    async listMarkdownFiles(): Promise<IFile[]> {
        return await this.app.listFiles();
    }

    /**
     * Récupère l'instance de l'app Nextcloud
     */
    getApp(): NextcloudApp {
        return this.app;
    }
}

// Export d'une instance globale pour faciliter l'utilisation
export const markdownCRM = new MarkdownCRMManager();
