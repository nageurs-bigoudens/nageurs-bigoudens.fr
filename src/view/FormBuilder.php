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

            $captcha = new Captcha;
            $_SESSION['captcha'] = $captcha->getSolution();

            $email = $email ?? Config::$email_dest;

            $admin_content = '';
            if($_SESSION['admin'])
            {
                $admin_content = '<div class="admin_form">
                    <p>
                        <label for="recipient">E-mail de destination de ce formulaire</label>
                        <input id="recipient" type="email" name="recipient" placeholder="mon-adresse@email.fr" value="' . $email . '">
                        <input type="hidden" id="recipient_hidden" value="">
                        <button onclick="changeRecipient(' . $node->getNodeData()->getId() . ')">Valider</button>
                    </p>
                    <p><button onclick="sendTestEmail(' . $node->getNodeData()->getId() . ')">Envoi d\'un e-mail de test</button></p>
                    <p class="test_email_success full_width_column"></p>
                </div>' . "\n";
            }

            ob_start();
            require $viewFile;
            $this->html = ob_get_clean(); // pas de concaténation ici, on écrase
        }
    }
}