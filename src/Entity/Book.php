<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * Many Users have Many Groups.
     * @var Collection<int, Author>
     */
    #[ORM\ManyToMany(targetEntity: Author::class, mappedBy: 'books')]
    private Collection $authors;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAuthors(): Collection
    {
        return $this->authors;
    }

    public function setAuthor(?Author $author): static
    {
        if ($author !== null && !$this->authors->contains($author)) {
            $this->authors->add($author);
            $author->addBook($this);
        } elseif ($author === null) {
            $this->authors->clear();
        }
        return $this;
    }

    public function addAuthor(Author $author): static
    {
        if (!$this->authors->contains($author)) {
            $this->authors->add($author);
            $author->addBook($this);
        }
        return $this;
    }

    public function removeAuthor(Author $author): static
    {
        if ($this->authors->removeElement($author)) {
            $author->removeBook($this);
        }
        return $this;
    }

    public function getAuthorCount(): int
    {
        return $this->authors->count();
    }
}
