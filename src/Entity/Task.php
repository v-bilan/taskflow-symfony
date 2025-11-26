<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use App\Repository\TaskRepository;
use App\State\TaskCollectionStateProvider;
use App\State\TaskSetOwnerProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            security:"is_granted('ROLE_USER')",
            normalizationContext: [
                'groups' => ['task:read:detail']
            ]
        ),
        new GetCollection(
            provider: TaskCollectionStateProvider::class,
            security:"is_granted('ROLE_USER')"
        ),
        new Post(
            security:"is_granted('ROLE_USER')",
            normalizationContext: [
                'groups' => ['task:read:detail']
            ]
        ),
        new Patch(
            security:"is_granted('TASK_EDIT', object)",

        ),
        new Delete(
            security:"is_granted('TASK_EDIT', object)"
        ),
    ],
    denormalizationContext: [
        'groups' => ['task:write']
    ],
    normalizationContext: [
        'groups' => ['task:read:list']
    ]
)]
class Task
{

    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['task:read:list', 'task:read:detail'])]
    private int $id;

    #[ORM\Column(length: 255)]
    #[Groups(['task:read:list', 'task:read:detail', 'task:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255, maxMessage: 'Tile should be 255 chars or less' )]
    private string $title;

    #[Groups(['task:read:detail', 'task:write'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: [self::STATUS_TODO, self::STATUS_DONE, self::STATUS_IN_PROGRESS])]
    #[Groups(['task:read:list', 'task:read:detail', 'task:write'])]
    #[ORM\Column(length: 20)]
    private ?string $status = null;
    #[Assert\Type(\DateTimeInterface::class)]
    #[Groups(['task:read:list', 'task:read:detail', 'task:write'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTime $dueDate = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['task:read:detail'])]
    private ?User $owner = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDueDate(): ?\DateTime
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTime $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getOwner(): ?user
    {
        return $this->owner;
    }

    public function setOwner(?user $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}
