<?php
// src/model/entities/EmailForm.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
//use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "email_form")]
class EmailForm{
	#[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_email_form;

    // inverseBy fait le lien avec $email_form dans Node (qui a "mappedBy")
    #[ORM\OneToOne(targetEntity: Node::class, inversedBy: "email_form")]
    #[ORM\JoinColumn(name: "node_id", referencedColumnName: "id_node")]
    private ?Node $node;

    #[ORM\Column(type: "json")]
    private array $data;

    public function __construct(array $data, Node $node){
        $this->data = $data;
        $this->node = $node;
    }

    public function getId(): int
    {
        return $this->id_email_form;
    }

    // getData et updateData sont indentiques au code dans NodeData
    // plutôt qu'une interface, pourquoi pas une classe abstraite? ou peut-être un trait?
    public function getData(): array
    {
        return $this->data;
    }
    public function updateData(string $key, string|int|bool|array $value = ''): void
    {
        if($value !== ''){
            $this->data[$key] = $value;
        }
        // si $value est vide, supprime la clé
        elseif(isset($this->data[$key])){
            unset($this->data[$key]);
        }
    }

    public function setNode(?Node $node): void
    {
    	$this->node = $node;
    }
}