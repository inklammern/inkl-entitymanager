<?php

namespace Inkl\EntityManager\Repository;

use Inkl\EntityManager\Entity\EntityInterface;
use Inkl\EntityManager\Factory\FactoryInterface;
use Doctrine\DBAL\Connection;
use Zend\Hydrator\HydratorInterface;


abstract class AbstractRepository implements RepositoryInterface {

	/** @var Connection */
	private $connection;
	/** @var FactoryInterface */
	private $factory;
	/** @var HydratorInterface */
	private $hydrator;
	/** @var string */
	private $mainTable;
	/** @var string */
	private $primaryKey;

	/**
	 * VideoRepository constructor.
	 * @param Connection $connection
	 * @param FactoryInterface $factory
	 * @param HydratorInterface $hydrator
	 * @param $mainTable
	 * @param $primaryKey
	 */
	public function __construct(Connection $connection, FactoryInterface $factory, HydratorInterface $hydrator, $mainTable, $primaryKey) {
		$this->connection = $connection;
		$this->factory = $factory;
		$this->hydrator = $hydrator;
		$this->mainTable = $mainTable;
		$this->primaryKey = $primaryKey;
	}


	public function getMainTable() {
		return $this->mainTable;
	}


	public function getPrimaryKey() {
		return $this->primaryKey;
	}


	public function getConnection() {
		return $this->connection;
	}


	public function getFactory() {
		return $this->factory;
	}


	public function getHydrator() {
		return $this->hydrator;
	}


	public function create() {
		return $this->factory->create();
	}


	public function load($id) {

		$primaryKey = $this->getPrimaryKey();

		$query = $this->connection->createQueryBuilder()
			->select('*')
			->from($this->getMainTable())
			->where($primaryKey . '=:' . $primaryKey)
			->setParameter($primaryKey, $id);

		$entity = $this->create();

		if ($data = $query->execute()->fetch()) {
			$this->hydrator->hydrate($data, $entity);
		}

		return $entity;
	}


	public function loadByField($value, $field) {

		$query = $this->connection->createQueryBuilder()
			->select('*')
			->from($this->getMainTable())
			->where($field . '=:' . $field)
			->setParameter($field, $value);

		$entity = $this->create();

		if ($data = $query->execute()->fetch()) {
			$this->hydrator->hydrate($data, $entity);
		}

		return $entity;
	}


	public function save(EntityInterface $entity) {

		$primaryKey = $this->getPrimaryKey();
		$data = $this->hydrator->extract($entity);

		if (isset($data[$primaryKey]) && !empty($data[$primaryKey])) {

			// update
			$this->connection->update($this->getMainTable(), $data, [$primaryKey => $data[$primaryKey]]);

		} else {

			// insert
			$this->connection->insert($this->getMainTable(), $data);

			// last_insert id
			$data[$primaryKey] = $this->connection->lastInsertId();
			$this->hydrator->hydrate($data, $entity);

		}

		return true;
	}


	public function delete(EntityInterface $entity) {

		$primaryKey = $this->getPrimaryKey();
		$data = $this->hydrator->extract($entity);

		if (isset($data[$primaryKey]) && $data[$primaryKey] > 0) {
			$this->connection->delete($this->getMainTable(), [$primaryKey => $data[$primaryKey]]);

			return true;
		}

		return false;
	}

}
