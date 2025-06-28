<?php
// src/view/FormBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class FormBuilder extends AbstractBuilder
{
    public function __construct(Node $node)
    {
        parent::__construct($node);
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';
        
        if(file_exists($viewFile))
        {
            if(!empty($node->getNodeData()->getData()))
            {
                extract($node->getNodeData()->getData());
            }

            $action_url = new URL(['page' => CURRENT_PAGE]);
            $captcha = new Captcha;
            $_SESSION['captcha'] = $captcha->getSolution();

            $no_recipient_warning = '';
            $admin_content = '';
            if($_SESSION['admin'])
            {
                $admin_content = '<script src="js/form.js"></script>
                <h3>Configuration du formulaire</h3>
                <div class="admin_form">
                    <label for="recipient">E-mail du destinataire</label>
                    <input id="recipient" type="email" name="recipient" placeholder="mon-adresse@email.fr" value="">
                    <button onclick="changeRecipient()">Valider</button>
                </div>';
            }

            $recipient_found = false;
            // recherche BDD
            
            if(!$recipient_found){ // vérifier qu'une adresse de destination est bien configurée
                $no_recipient_warning = '<p class="no_recipient_warning">Aucune adresse de destination n\'a été configurée, envoi d\'e-mail impossible!</p>';
            }

            ob_start();
            require $viewFile;
            $this->html = ob_get_clean(); // pas de concaténation ici, on écrase
        }
    }
}