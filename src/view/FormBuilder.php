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

            $recipient_found = false;
            if(isset($email)){
                $recipient_found = true;
            }
            else{
                $email = '';
            }

            $admin_content = '';
            if($_SESSION['admin'])
            {
                $admin_content = '<script src="js/form.js"></script>
                <h3>Configuration du formulaire</h3>
                <div class="admin_form">
                    <label for="recipient">E-mail de destination</label>
                    <input id="recipient" type="email" name="recipient" placeholder="mon-adresse@email.fr" value="' . $email . '">
                    <button onclick="changeRecipient(' . $node->getNodeData()->getId() . ')">Valider</button>
                </div>';
            }
            
            // vérifier qu'une adresse de destination est bien configurée
            $no_recipient_warning = '<p class="no_recipient_warning ' . ($recipient_found ? 'hidden' : '') . '">Aucune adresse de destination n\'a été configurée, envoi d\'e-mail impossible!</p>';

            ob_start();
            require $viewFile;
            $this->html = ob_get_clean(); // pas de concaténation ici, on écrase
        }
    }
}