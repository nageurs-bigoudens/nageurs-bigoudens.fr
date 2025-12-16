<?php
// src/view/ShowEmailsBuilder.php

declare(strict_types=1);

use App\Entity\Node;
use App\Entity\Page;

class ShowEmailsBuilder extends AbstractBuilder
{
    public function __construct(Node $node = null)
    {
        //parent::__construct($node);
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';
        if(file_exists($viewFile))
        {
            // objets Email groupés par destinataire
            $emails_by_recipient = [];
            foreach($node->getNodeData()->getEmails() as $email){
                $recipient = $email->getRecipient();
                $emails_by_recipient[$recipient][] = $email;
            }

            // affiche une table par destinataire
            $emails = '';
            foreach($emails_by_recipient as $recipient => $emails_list){
                $html = '<h4>Destinataire: ' . $recipient . '</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Expéditeur</th>
                                <th>Adresse</th>
                                <th>Contenu</th>
                                <th>Date</th>
                                <th>Effacement prévu le</th>
                                <th>Sensible</th>
                                <th class="email_delete_button"></th>
                            </tr>
                        </thead>
                        <tbody>';

                // insère les données
                foreach($emails_list as $email){
                    $html .= '<tr id="' . $email->getId() . '">
                        <td>' . htmlspecialchars($email->getSenderName()) . '</td>
                        <td>' . htmlspecialchars($email->getSenderAddress()) . '</td>
                        <td>' . htmlspecialchars($email->getContent()) . '</td>
                        <td>' . $email->getDateTime()->format('d/m/Y') . '</td>
                        <td class="deletion_date">' . $email->getDeletionDate()->format('d/m/Y') . '</td>
                        <td><input class="make_checkbox_sensitive" type="checkbox" ' . ($email->isSensitive() ? 'checked' : '') . ' onclick="toggleSensitiveEmail(' . $email->getId() . ')"></td>
                        <td class="email_delete_button"><img class="action_icon" src="assets/delete-bin.svg" onclick="deleteEmail(' . $email->getId() . ')"></td>
                    </tr>';
                }

                $html .= '</tbody>
                    </table>';
                $emails .= $html;
            }

            ob_start();
            require $viewFile; // insertion de $this->html généré par unfoldMenu
            $this->html = ob_get_clean(); // pas de concaténation .= cette fois on écrase
        }
        else{
            header('Location: ' . new URL(['error' => 'show_emails_view_not_found']));
            die;
        }
    }
}