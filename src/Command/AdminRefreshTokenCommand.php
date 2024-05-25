<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(name: 'admin:refresh-token', description: 'Refreshes the token of an admin user.',)]
class AdminRefreshTokenCommand extends Command
{
    private const int NOT_FOUND_ADMIN = 0;
    private const int MORE_THAN_ONE   = 1;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly userRepository $userRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('uuid', InputArgument::OPTIONAL, InputOption::VALUE_OPTIONAL, 'Admin uuid', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $uuid = $input->getOption('uuid');

        if ($uuid) {
            return $this->handleUuidOption($uuid, $io);
        }

        $numberOfAdmins = $this->userRepository->createQueryBuilder('u')
                                               ->select('count(u.uuid)')
                                               ->where('u.token IS NOT NULL')
                                               ->getQuery()
                                               ->getSingleScalarResult()
        ;

        $this->validateNumberOfAdmins($numberOfAdmins, $io);

        if ($numberOfAdmins > self::MORE_THAN_ONE) {
            $uuid = $this->askAdminUuid($input, $output, $io);
            if ($uuid === Command::FAILURE) {
                return Command::FAILURE;
            }
        }

        if ($uuid === null) {
            $uuid = $this->userRepository->createQueryBuilder('u')
                                         ->select('u.uuid')
                                         ->where('u.token IS NOT NULL')
                                         ->setMaxResults(1)
                                         ->getQuery()
                                         ->getSingleScalarResult()
            ;
        }

        return $this->refreshAdminToken($uuid, $io);
    }

    private function handleUuidOption(mixed $uuid, SymfonyStyle $io): int
    {
        $uuid = $this->validateUuid($uuid, $io);
        //        if ($uuid === Command::FAILURE) {
        //            return Command::FAILURE;
        //        }

        return $this->refreshAdminToken($uuid, $io);
    }

    private function validateNumberOfAdmins(int $numberOfAdmins, SymfonyStyle $io): int
    {
        if ($numberOfAdmins === self::NOT_FOUND_ADMIN) {
            $io->note('Администратор отсутствует, создайте его.');
            $io->block('bin/console admin:create-new');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function validateUuid(Uuid $uuid, $io): Uuid|int
    {
        try {
            $uuid = Uuid::fromString($uuid);
        } catch (\InvalidArgumentException) {
            $io->error('Неверный UUID.');
            return Command::FAILURE;
        }

        return $uuid;
    }

    private function validateAdmin(?User $admin, $io): true|int
    {
        if ($admin === null || !in_array('ROLE_ADMIN', $admin?->getRoles(), true)) {
            $io->error('Администратор не найден.');
            return Command::FAILURE;
        }

        return true;
    }

    private function askAdminUuid(InputInterface $input, OutputInterface $output, SymfonyStyle $io): Uuid|int
    {
        $helper   = $this->getHelper('question');
        $question = new Question('Введите UUID администратора: ');
        $uuid     = $helper->ask($input, $output, $question);

        return $this->validateUuid($uuid, $io);
    }

    private function refreshAdminToken(Uuid|string|null $uuid, SymfonyStyle $io): int
    {
        $admin = $this->userRepository->findOneBy(['uuid' => $uuid]);

        $this->validateAdmin($admin, $io);

        $admin->setToken(Uuid::v4());

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success("You have a new token {$admin->getToken()} for admin {$admin->getUuid()}");
        return Command::SUCCESS;
    }
}
