<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Controller\RankingController;
use App\Controller\Ship\ShipChatController;
use App\Controller\Ship\ShipEconomyController;
use App\Controller\Ship\ShipEnrollmentController;
use App\Controller\Ship\ShipNotificationsController;
use App\Controller\Ship\ShipOverviewController;
use App\Controller\Ship\ShipRosterController;
use App\Controller\ShipsFightController;
use App\Controller\WebSocketTokenController;
use App\Repository\ShipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: ShipRepository::class)]
#[ORM\Table(name: 'ship')]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/rankings/ships',
            controller: RankingController::class.'::getShipsRanking',
            name: 'api_rankings_ships',
            read: false
        ),
        new Get(
            uriTemplate: '/ships/my-ship',
            controller: ShipOverviewController::class.'::getMyShip',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/ships/{id}/preview',
            controller: ShipOverviewController::class.'::getShipPreview',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            securityMessage: 'Access denied. You must be authenticated to view ship previews.',
        ),
        new Post(
            uriTemplate: '/ships/create',
            controller: ShipOverviewController::class.'::createShip',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/update',
            controller: ShipOverviewController::class.'::updateShip',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/send-message',
            controller: ShipChatController::class.'::sendMessage',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/ships/messages',
            controller: ShipChatController::class.'::getMessages',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/ships/chat/token',
            controller: WebSocketTokenController::class.'::getShipChatToken',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/invite-member',
            controller: ShipRosterController::class.'::inviteMember',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/remove-member',
            controller: ShipRosterController::class.'::removeMember',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/leave',
            controller: ShipRosterController::class.'::leaveShip',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/transfer-ownership',
            controller: ShipRosterController::class.'::transferOwnership',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/change-member-role',
            controller: ShipRosterController::class.'::changeMemberRole',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/ships/search-users',
            controller: ShipRosterController::class.'::searchUsers',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/delete',
            controller: ShipOverviewController::class.'::deleteShip',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/deposit',
            controller: ShipEconomyController::class.'::depositToShip',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/upgrade',
            controller: ShipEconomyController::class.'::upgradeShip',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/set-invitation-required',
            controller: ShipEnrollmentController::class.'::setInvitationRequired',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/join',
            controller: ShipEnrollmentController::class.'::joinShip',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/request-to-join',
            controller: ShipEnrollmentController::class.'::requestToJoin',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/cancel-join-request',
            controller: ShipEnrollmentController::class.'::cancelJoinRequest',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/cancel-invitation',
            controller: ShipEnrollmentController::class.'::cancelInvitation',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/ships/my-invitations',
            controller: ShipEnrollmentController::class.'::getMyInvitations',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/accept-invitation',
            controller: ShipEnrollmentController::class.'::acceptInvitation',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/decline-invitation',
            controller: ShipEnrollmentController::class.'::declineInvitation',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/ships/my-join-requests',
            controller: ShipEnrollmentController::class.'::getMyJoinRequests',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/approve-join-request',
            controller: ShipEnrollmentController::class.'::approveJoinRequest',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/reject-join-request',
            controller: ShipEnrollmentController::class.'::rejectJoinRequest',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/ships/unread-notifications-count',
            controller: ShipNotificationsController::class.'::getUnreadNotificationsCount',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/mark-invitation-read',
            controller: ShipNotificationsController::class.'::markInvitationAsRead',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/mark-join-request-read',
            controller: ShipNotificationsController::class.'::markJoinRequestAsRead',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/ships/my-removal-notifications',
            controller: ShipNotificationsController::class.'::getMyRemovalNotifications',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/mark-removal-notification-read',
            controller: ShipNotificationsController::class.'::markRemovalNotificationAsRead',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/ships/my-fight-notifications',
            controller: ShipNotificationsController::class.'::getMyFightNotifications',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/mark-fight-notification-read',
            controller: ShipNotificationsController::class.'::markFightNotificationAsRead',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/ships/fights/opponents',
            controller: ShipsFightController::class.'::getOpponents',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/ships/fights/can-start',
            controller: ShipsFightController::class.'::canStartFight',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/ships/fights/start',
            controller: ShipsFightController::class.'::startFight',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/ships/fights/history',
            controller: ShipsFightController::class.'::getFightHistory',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/ships/fights/{fightId}',
            controller: ShipsFightController::class.'::getFightDetails',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ]
)]
class Ship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ship:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['ship:read', 'user:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['ship:read', 'user:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['ship:read'])]
    private ?string $internalNotes = null;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private int $gold = 0;

    #[ORM\Column(name: 'diamonds')]
    #[Groups(['ship:read'])]
    #[SerializedName('diamonds')]
    private int $diamonds = 0;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private int $famePoints = 0;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private int $skillsUpgrade = 0;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private int $workUpgrade = 0;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private int $missionsUpgrade = 0;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['ship:read'])]
    private int $hullUpgrade = 0;

    #[ORM\Column(options: ['default' => 10])]
    #[Groups(['ship:read'])]
    private int $maxMembers = 10;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['ship:read'])]
    private bool $requiresInvitation = true;

    #[ORM\OneToMany(mappedBy: 'ship', targetEntity: ShipMember::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['ship:read'])]
    #[MaxDepth(1)]
    private Collection $members;

    #[ORM\OneToMany(mappedBy: 'ship', targetEntity: ShipMessage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['ship:read'])]
    private Collection $messages;

    #[ORM\OneToMany(mappedBy: 'ship', targetEntity: ShipJoinRequest::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $joinRequests;

    #[ORM\OneToMany(mappedBy: 'ship', targetEntity: ShipInvitation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $invitations;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->joinRequests = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->maxMembers = 10;
        $this->hullUpgrade = 0;
        $this->requiresInvitation = true;
    }

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

    public function getInternalNotes(): ?string
    {
        return $this->internalNotes;
    }

    public function setInternalNotes(?string $internalNotes): static
    {
        $this->internalNotes = $internalNotes;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(ShipMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setShip($this);
        }

        return $this;
    }

    public function removeMember(ShipMember $member): static
    {
        if ($this->members->removeElement($member)) {
            if ($member->getShip() === $this) {
                $member->setShip(null);
            }
        }

        return $this;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(ShipMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setShip($this);
        }

        return $this;
    }

    public function removeMessage(ShipMessage $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getShip() === $this) {
                $message->setShip(null);
            }
        }

        return $this;
    }

    public function getJoinRequests(): Collection
    {
        return $this->joinRequests;
    }

    public function addJoinRequest(ShipJoinRequest $joinRequest): static
    {
        if (!$this->joinRequests->contains($joinRequest)) {
            $this->joinRequests->add($joinRequest);
            $joinRequest->setShip($this);
        }

        return $this;
    }

    public function removeJoinRequest(ShipJoinRequest $joinRequest): static
    {
        if ($this->joinRequests->removeElement($joinRequest)) {
            if ($joinRequest->getShip() === $this) {
                $joinRequest->setShip(null);
            }
        }

        return $this;
    }

    public function getInvitations(): Collection
    {
        return $this->invitations;
    }

    public function addInvitation(ShipInvitation $invitation): static
    {
        if (!$this->invitations->contains($invitation)) {
            $this->invitations->add($invitation);
            $invitation->setShip($this);
        }

        return $this;
    }

    public function removeInvitation(ShipInvitation $invitation): static
    {
        if ($this->invitations->removeElement($invitation)) {
            if ($invitation->getShip() === $this) {
                $invitation->setShip(null);
            }
        }

        return $this;
    }

    public function getGold(): int
    {
        return $this->gold;
    }

    public function setGold(int $gold): static
    {
        $this->gold = $gold;

        return $this;
    }

    public function addGold(int $amount): static
    {
        $this->gold += $amount;

        return $this;
    }

    public function getDiamonds(): int
    {
        return $this->diamonds;
    }

    public function setDiamonds(int $diamonds): static
    {
        $this->diamonds = $diamonds;

        return $this;
    }

    public function addDiamonds(int $amount): static
    {
        $this->diamonds += $amount;

        return $this;
    }

    public function getFamePoints(): int
    {
        return $this->famePoints;
    }

    public function setFamePoints(int $famePoints): static
    {
        $this->famePoints = $famePoints;

        return $this;
    }

    public function addFamePoints(int $amount): static
    {
        $this->famePoints += $amount;

        return $this;
    }

    public function getSkillsUpgrade(): int
    {
        return $this->skillsUpgrade;
    }

    public function setSkillsUpgrade(int $skillsUpgrade): static
    {
        $this->skillsUpgrade = $skillsUpgrade;

        return $this;
    }

    public function getWorkUpgrade(): int
    {
        return $this->workUpgrade;
    }

    public function setWorkUpgrade(int $workUpgrade): static
    {
        $this->workUpgrade = $workUpgrade;

        return $this;
    }

    public function getMissionsUpgrade(): int
    {
        return $this->missionsUpgrade;
    }

    public function setMissionsUpgrade(int $missionsUpgrade): static
    {
        $this->missionsUpgrade = $missionsUpgrade;

        return $this;
    }

    public function getHullUpgrade(): int
    {
        return $this->hullUpgrade;
    }

    public function setHullUpgrade(int $hullUpgrade): static
    {
        $this->hullUpgrade = $hullUpgrade;

        return $this;
    }

    public function getMaxMembers(): int
    {
        return $this->maxMembers;
    }

    public function setMaxMembers(int $maxMembers): static
    {
        $this->maxMembers = $maxMembers;

        return $this;
    }

    public function getRequiresInvitation(): bool
    {
        return $this->requiresInvitation;
    }

    public function setRequiresInvitation(bool $requiresInvitation): static
    {
        $this->requiresInvitation = $requiresInvitation;

        return $this;
    }
}
