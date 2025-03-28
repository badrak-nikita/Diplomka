<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function getTotalRevenue(): float
    {
        return $this->createQueryBuilder('o')
            ->select('SUM(s.price)')
            ->join('o.service', 's')
            ->andWhere('o.status = :status')
            ->setParameter('status', 2)
            ->getQuery()
            ->getSingleScalarResult()
            ?? 0;
    }
}
