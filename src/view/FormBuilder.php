<?php
// src/view/FormBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class FormBuilder extends AbstractBuilder
{
    static private ?Captcha $captcha = null;

    public function __construct(Node $node)
    {
        parent::__construct($node);
        
        if(!empty($node->getNodeData()->getData()))
        {
            extract($node->getNodeData()->getData());
        }

        // un seul captcha à la fois!
        if(!self::$captcha){
            self::$captcha = new Captcha;
            $_SESSION['captcha'] = self::$captcha->getSolution();
        }

        $smtp_host = $smtp_host ?? Config::$smtp_host;
        $smtp_secure = $smtp_secure ?? Config::$smtp_secure;
        $smtp_username = $smtp_username ?? Config::$smtp_username;
        $email_dest = $email_dest ?? Config::$email_dest;

        $admin_content = '';
        if($_SESSION['admin'])
        {
            ob_start();
            require self::VIEWS_PATH . 'form_params.php';
            $admin_content = ob_get_clean();
        }

        ob_start();
        require self::VIEWS_PATH . $node->getName() . '.php';
        $this->html = ob_get_clean(); // pas de concaténation ici, on écrase
    }
}