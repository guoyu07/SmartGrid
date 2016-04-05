<?php

namespace SmartGrid\data;

use SmartGrid\helpers\ValueFormatter;

class CodeIgniterDataProvider extends DataProvider
{
	private $controller;

	public function __construct($context, $data)
	{
		$this->controller = $context;
		$this->options = ValueFormatter::extend($this->options, $data);

	}

	public function run()
	{
		$currentPage = $this->controller->input->get('current-page');
		$currentPage = ($currentPage == '' || $currentPage == null || $currentPage == 0 ? 1 : $currentPage) - 1;
		$currentPage = $currentPage < 0 ? 1 : $currentPage;

		$sortByUrl = $this->controller->input->get('sort');

		if ($this->options['table'] !== '') {
			$query = clone $this->controller->db;

			// sort
			if (empty($sortByUrl)) {
				foreach ($this->options['sort']['defaultOrder'] as $key => $value) {
					$query->order_by($key, $value);
				}
			} else {
				foreach ($sortByUrl as $key => $value) {
					$query->order_by($key, $value);
				}
			}

			// filter
			$filters = $this->controller->input->get('filter', true);
			if (!empty($filters) && is_array($filters)) {
				foreach ($filters as $key => $value) {
					if (!empty($value)) {
						$query->like($key, $value);
					}
				}
			}

			$queryTotal = clone $query;

			// limit offset
			if (is_numeric($this->options['pagination']['pageSize'])) {
				$query->limit($this->options['pagination']['pageSize'], ($this->options['pagination']['pageSize'] * $currentPage));
			}
			$records = $query->get($this->options['table'])->result_array();

			// total
			$queryTotal->limit('', '');
			$total = count($queryTotal->get($this->options['table'])->result_array());

			$this->result = ValueFormatter::extend($this->result, array(
					'pagination' => array(
						'pageSize' => $this->options['pagination']['pageSize'],
						'currentPage' => $currentPage + 1,
						'total' => $total
					),
					'defaultOrder' => $this->options['sort']['defaultOrder'],
					'records' => $records
			));
		} else if ($this->options['query'] !== '') {

			$this->result = ValueFormatter::extend($this->result, array(
					'pagination' => array(
						'pageSize' => $this->options['pagination']['pageSize'],
						'currentPage' => $currentPage + 1,
						'total' => $this->options['pagination']['total']
					),
					'defaultOrder' => $this->options['sort']['defaultOrder'],
					'records' => $this->options['query']->result_array()
			));
		}

		return $this->result;

	}
}