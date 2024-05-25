<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'admin:create-new',
    description: 'Create a new admin account.',
)]
class CreateAdminCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $admin = new User();
        $admin->setUuid(Uuid::v4());
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setToken(Uuid::v4());

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success("You have a new admin with token: {$admin->getToken()}");

        return Command::SUCCESS;
    }
}
