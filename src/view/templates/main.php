<?php declare(strict_types=1); ?>
<section class="page_modification">
    <h3>Modification de la page</h3>
    <div class="edit_page_zone">
        <div class="edit_page_title_zone">
            <p id="edit_page_title">
                <label for="page_name">Titre de la page</label>
                <input type="text" id="page_name" name="edit_page_title" value="<?= Director::$page_path->getLast()->getPageName() ?>" onchange="makePageNamePath()" required>
                <button onclick="changePageTitle('<?= Director::$page_path->getLast()->getId() ?>')">Renommer</button>
            </p>
            <form id="edit_page_menu_path" method="post" action="<?= new URL(['page' => CURRENT_PAGE]) ?>">
                <label for="page_name_path">Chemin en "snake_case"</label>
                <input type="text" id="page_name_path" name="page_menu_path" value="<?= Director::$page_path->getLast()->getEndOfPath() ?>" placeholder="ex: nouvelle_page" required>
                <input type="hidden" name="page_id" value="<?= Director::$page_path->getLast()->getId() ?>"><input type="hidden" name="page_name_path_hidden">
                <input type="submit" value="Modifier">
            </form>
        </div>
        <div id="edit_description">
            <label for="description_textarea">Description sous le titre dans les moteurs de recherche</label>
            <div>
                <textarea id="description_textarea" name="edit_description" cols="40" rows="3" placeholder="ex: nous faisons ceci et cela, etc" required><?= $head_node->getNodeData()->getData()['description'] ?></textarea>
                <button onclick="changeDescription('<?= $head_node->getNodeData()->getId() ?>')">Modifier</button>
            </div>
        </div>
    </div>
    <div class="delete_page_zone">
        <form method="post" action="<?= new URL ?>">
            <label>Supprimer cette page</label>
            <input type="hidden" name="page_id" value="<?= Director::$page_path->getLast()->getId() ?>">
            <input type="hidden" name="submit_hidden">
            <input type="submit" value="Valider" onclick="return confirm('Voulez-vous vraiment supprimer cette page?');">
        </form>
    </div>
    <div class="edit_bloc_zone">
        <div class="new_bloc">
            <p>Ajouter un bloc de page</p>
            <form method="post" action="<?= new URL(['page' => CURRENT_PAGE]) ?>">
                <p><label for="bloc_title">Titre</label>
                <input type="text" id="bloc_title" name="bloc_title" required></p>
                <p><label for="bloc_select">Type</label>
                <select id="bloc_select" name="bloc_select" required>
                 <?= $options ?>
                </select>
                <input type="hidden" name="bloc_title_hidden">
                <input type="submit" value="Valider"></p>
            </form>
        </div>
        <div class="modify_bloc">
            <p>Modifier un bloc</p>
            <?= $bloc_edit ?>
        </div>
    </div>
</section>