<?php
style('crm', 'crm-main');
style('crm', 'markdown-crm-display');
?>

<div id="crm-app">
    <aside id="crm-sidebar">
        <div class="sidebar-header">
            <h2>📁 Fichiers</h2>
        </div>
        <div id="crm-file-tree"></div>
    </aside>
    
    <main id="crm-content">
        <div id="crm-metadata-panel"></div>
        <div id="crm-editor-container"></div>
    </main>
</div>

<?php
// Charger le script à la fin pour que OC soit disponible
script('crm', 'main');
?>
