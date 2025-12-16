<?php

namespace App\Entity;

use Andante\TimestampableBundle\Timestampable\TimestampableInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Controller\AddCommetntForTaskController;
use App\Repository\CommentRepository;
use App\State\CommentCollectionStateProvider;
use App\State\CommentSetOwnerAndTaskProcessor;
use App\State\TaskCommentCollectionStateProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ApiResource(
    operations:[
        new Get(
            security: 'is_granted("COMMENT_VIEW", object)'
        ),
        new GetCollection(
            security: 'is_granted("ROLE_USER")',
            provider: CommentCollectionStateProvider::class
        ),

        new GetCollection(
            uriTemplate: '/tasks/{id}/comments',
            uriVariables: [
                'id' => new Link(
                    fromClass: Task::class, 
                    fromProperty: 'comments',
                    toProperty: 'task',
                    security: 'is_granted("TASK_VIEW", task)'
                )
                ],
                provider: TaskCommentCollectionStateProvider::class
        ),
        new Post(
            uriTemplate: '/tasks/{id}/comments',
            read: false,
            denormalizationContext: ['groups' => ['comment:write']],
           
        ),
        new Patch(
            security:"is_granted('COMMENT_EDIT', object)",
        ),
        new Delete(
            security:"is_granted('COMMENT_DELETE', object)",
        ),
    /*    
        new Post(
            uriTemplate: '/tasks/{id}/comments',
            controller: AddCommetntForTaskController::class,
            deserialize: false,
            denormalizationContext: [
                'groups' => ['comment:write']
            ],
            normalizationContext: [
              'groups'=> ['comment:read']
            ]
        )
        */    
            
    ],
    normalizationContext: [
        'groups'=> ['comment:read']
    ]
)]

class Comment implements TimestampableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['comment:read', 'task:read:comments'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['comment:read', 'comment:write', 'task:read:comments'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 3, 
        max: 255, 
        minMessage: 'Message should be {{ limit }} chars or more', 
        maxMessage: 'Message should be {{ limit }} chars or less'
    )]
    private ?string $message = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['comment:read'])]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['comment:read'])]
    private ?Task $task = null;

    #[Groups(['comment:read'])]
    private ?\DateTimeImmutable $createdAt = null;
    #[Groups(['comment:read'])]
    private ?\DateTimeImmutable $updatedAt = null;
    
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(Task $task) 
    {
        $this->task = $task;    
    }

    public function setCreatedAt(\DateTimeImmutable $dateTime): void
    {
        $this->createdAt = $dateTime;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    public function setUpdatedAt(\DateTimeImmutable $dateTime): void
    {
        $this->updatedAt = $dateTime;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
