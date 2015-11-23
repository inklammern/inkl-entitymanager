<?php

namespace Inkl\EntityManager\Collection;

use Inkl\EntityManager\Repository\RepositoryInterface;
use Doctrine\DBAL\Query\QueryBuilder;

abstract class AbstractCollection implements \IteratorAggregate {

	protected $items = [];

	/** @var RepositoryInterface */
	protected $repository;

	/** @var QueryBuilder */
	protected $queryBuilder;

	/**
	 * @param RepositoryInterface $repository
	 */
	public function __construct(RepositoryInterface $repository) {
		$this->repository = $repository;

		$this->initQueryBuilder();
	}


	protected function initQueryBuilder() {

		$this->queryBuilder = $this->repository->getConnection()->createQueryBuilder()
				->select('*')
				->from($this->repository->getMainTable(), 'main_table');
	}


	public function load() {

		$stmt = $this->queryBuilder->execute();

		$this->items = [];
		while ($data = $stmt->fetch()) {
			$this->items[] = $this->repository->getHydrator()->hydrate($data, $this->repository->getFactory()->create());
		}

	}


	public function getCount() {

		$queryBuilder = clone $this->queryBuilder;

		$stmt = $queryBuilder->select('COUNT(*)')->execute();

		return current($stmt->fetch());
	}


	public function getIterator() {

		$this->load();

		return new \ArrayIterator($this->items);
	}


	public function getQueryBuilder() {
		return $this->queryBuilder;
	}


	public function setPage($pageNum, $pageSize) {
		$this->queryBuilder
				->setFirstResult(($pageNum-1) * $pageSize)
				->setMaxResults($pageSize);

		return $this;
	}

}
