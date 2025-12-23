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

        // données stockées en vrac dans du JSON et récupérées avec extract => changer ça un jour
        $smtp_host = $smtp_host ?? Config::$smtp_host;
        $smtp_secure = $smtp_secure ?? Config::$smtp_secure;
        $smtp_username = $smtp_username ?? Config::$smtp_username;
        $email_dest = $email_dest ?? Config::$email_dest;
        $keep_emails = (bool)$keep_emails ?? false; // (bool) est inutile mais plus clair
        $retention_period = (int)($retention_period ?? App\Entity\Email::DEFAULT_RETENTION_PERIOD); // (int) est nécessaire à cause du stockage JSON

        $admin_content = '';
        if($_SESSION['admin'])
        {
            ob_start();
            require self::VIEWS_PATH . 'form_admin.php';
            $admin_content = ob_get_clean();
        }

        ob_start();
        require self::VIEWS_PATH . $node->getName() . '.php';
        $this->html = ob_get_clean(); // pas de concaténation ici, on écrase
    }
}