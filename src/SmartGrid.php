<?php

namespace SmartGrid;

use SmartGrid\base\Widget;
use SmartGrid\helpers\Html;
use SmartGrid\helpers\UrlManager;
use SmartGrid\helpers\ValueFormatter;

class SmartGrid extends Widget
{
	/**
	 * Pagination Type
	 */
	const PAGINATION_TYPE_NUMERIC = 'numeric';
	const PAGINATION_TYPE_NEXTPREV = 'nextprev';
	/**
	 * Panel Type
	 */
	const PANEL_TYPE_DEFAULT = 'panel-default';
	const PANEL_TYPE_PRIMARY = 'panel-primary';
	const PANEL_TYPE_SUCCESS = 'panel-success';
	const PANEL_TYPE_INFO = 'panel-info';
	const PANEL_TYPE_WARNING = 'panel-warning';
	const PANEL_TYPE_DANGER = 'panel-danger';
	/**
	 * Pagination Options
	 * @var array
	 */
	public $pagination = [
		'enabled' => true,
		'showAllRecords' => false,
		'type' => 'numeric',
		'centerPosition' => 3
	];
	/**
	 * @var object
	 */
	protected $paginatioObject = null;
	public $pageSummary = true;
	/**
	 * @var string 
	 */
	protected $pageSummaryString = null;
	/**
	 * Panel Options
	 * @var array
	 */
	public $panel = [
		'enabled' => true,
		'type' => 'panel-primary',
		'heading' => '&nbsp',
		'headingHtmlOptions' => [
			'class' => 'panel-heading'
		]
	];
	/**
	 *
	 * @var array
	 */
	public $toolbar = [
		'enabled' => true,
		'pagination' => false,
		'pageSummary' => true
	];
	/**
	 *
	 * @var array
	 */
	public $table = [
		'bordered' => true,
		'condensed' => true,
		'striped' => true,
		'hover' => true,
		'responsive' => true,
		'responsiveWrap' => false,
		'htmlOptions' => [
			'class' => 'table'
		],
		'header' => true,
		'footer' => false,
	];
	/**
	 * @var boolean
	 */
	public $sort = true;
	/**
	 * @var boolean
	 */
	public $filter = true;
	/**
	 * @var boolean 
	 */
	public $tableScrollVertical = true;
	/**
	 * @var boolean
	 */
	public $selectableCell = true;
	/**
	 * @var array
	 */
	public $tableContainerHtmlOptions = [];
	/**
	 * @var string
	 */
	public $tableContainerClass = 'pfs-sg-container';
	/**
	 * @var array
	 */
	public $dataProvider = [];
	/**
	 * @var array
	 */
	public $columns = [];
	/**
	 * @var string
	 */
	public $csrfToken = null;
	/**
	 * @var string
	 */
	public $csrfTokenName = null;

	public function run()
	{
		$this->dataProvider = $this->dataProvider->run();
		$this->columns = $this->initColumns();
		if (!is_numeric($this->dataProvider['pagination']['pageSize'])) {
			$this->pagination['enabled'] = false;
		}
		if ($this->pagination['enabled'] === true && $this->pagination['showAllRecords'] === false) {
			$this->paginatioObject = $this->renderPagination();
		}
		if ($this->pageSummary === true) {
			$this->pageSummaryString = $this->renderPageSummary();
		}
		if ($this->table['bordered']) {
			Html::addCssClass('table-bordered', $this->table['htmlOptions']);
		}
		if ($this->table['striped']) {
			Html::addCssClass('table-striped', $this->table['htmlOptions']);
		}
		if ($this->table['condensed']) {
			Html::addCssClass('table-condensed', $this->table['htmlOptions']);
		}
		if ($this->table['hover']) {
			Html::addCssClass('table-hover', $this->table['htmlOptions']);
		}

		return $this->renderPanel();

	}

	/**
	 * Format column
	 * @return array
	 */
	public function initColumns()
	{
		if (empty($this->columns)) {
			return [];
		} else {
			$data = [];
			foreach ($this->columns as $key => $value) {
				if (is_array($value)) {
					array_push($data, $value);
				} else {
					array_push($data, [
						'name' => $value,
						'label' => $value
					]);
				}
			}
			return $data;
		}

	}

	/**
	 * Render panel
	 * 
	 * @param string $table
	 * @return string
	 */
	public function renderPanel()
	{
		$content = [];
		$htmlOptions = ['class' => 'panel', 'id' => $this->id];

		if ($this->panel['enabled'] === true) {
			$title = [];
			if ($this->pageSummary === true) {
				$title[] = Html::tag('div', ['class' => 'pull-right'], $this->pageSummaryString);			
			}
			$title[] = Html::tag('h3', ['class' => 'panel-title'], $this->panel['heading']);
							
			
			$content[] = Html::tag('div', $this->panel['headingHtmlOptions'], implode('', $title));
			Html::addCssClass($this->panel['type'], $htmlOptions);
		} else {
			Html::removeAttribute('class', 'panel', $htmlOptions);
			Html::removeAttribute('class', $this->panel['type'], $htmlOptions);
		}

		$content[] = $this->renderGrid();
		return Html::tag('div', $htmlOptions, implode('', $content));

	}

	/**
	 * Generate table and all component SmartGrid
	 * 
	 * @return string
	 */
	public function renderGrid()
	{
		$toolbar = $this->toolbar['enabled'] ? $this->renderToolbar() : false;
		$tableHeader = $this->table['header'] ? $this->renderTableHeader() : false;
		$tableBody = $this->renderTableBody();

		$content = [
			$toolbar,
			$tableHeader,
			$tableBody,
		];
		Html::addCssClass($this->tableContainerClass, $this->tableContainerHtmlOptions);
		return Html::tag('div', $this->tableContainerHtmlOptions, implode('', $content));

	}

	public function renderTableHeader()
	{
		$cells = [];
		$index = 0;
		foreach ($this->columns as $key => $value) {
			$index++;
			$cells[] = $this->renderHeaderCell($value, $index);
		}
		$tr = [
			Html::tag('tr', null, implode('', $cells))
		];

		if ($this->filter === true) {
			$tr[] = $this->renderHeaderFilter();
		}

		$thead = Html::tag('thead', null, implode('', $tr));
		$table = Html::tag('table', $this->table['htmlOptions'], $thead);

		if ($this->filter === true) {
			$formOptions = [
				'action' => $this->buildUrl(),
				'method' => 'GET',
				'data-pfs-sg-ajax-filter' => 'true'
			];
			$submit = Html::tag('button', ['style' => 'display: none', 'type' => 'submit'], '');
			$form = Html::tag('form', $formOptions, $submit . $table);
			return Html::tag('div', ['class' => 'header'], $form);
		}

		return Html::tag('div', ['class' => 'header'], $table);

	}

	public function renderHeaderCell($options, $index)
	{
		$label = Html::getValue($options, 'label');
		$name = Html::getValue($options, 'name', false);

		if ($this->sort && $name !== false) {
			$linkOption = [];
			$url = $this->getOrderUrl($name);
			Html::addAttribute('href', $url, $linkOption);

			$iconName = 'both';
			$queryParams = UrlManager::getQueryParams();
			if (!empty($queryParams['sort'])) {
				if ($this->getSortActive($name) !== false) {
					$iconName = strtolower($this->getSortActive($name));
				}
			} else {
				$defaultOrder = $this->dataProvider['defaultOrder'];

				if (!empty($defaultOrder[$name])) {
					unset($linkOption['href']);
					$url = $this->getOrderUrl($name, $defaultOrder[$name]);
					Html::addAttribute('href', $url, $linkOption);
					$iconName = strtolower($defaultOrder[$name]);
				}
			}

			Html::addAttribute('data-pfs-sg-ajax-link', 'true', $linkOption);
			Html::addAttribute('class', 'icon-sort', $linkOption);
			Html::addAttribute('class', 'icon-sort-' . $iconName, $linkOption);
			$label = Html::tag('a', $linkOption, $label);
		}

		$thOptions = [];
		if (isset($options['headerHtmlOptions'])) {
			$thOptions = ValueFormatter::extend($thOptions, $options['headerHtmlOptions']);
		}

		Html::addAttribute('data-pfs-sg-title-position', $index, $thOptions);
		return Html::tag('th', $thOptions, $label);

	}

	public function renderHeaderFilter()
	{
		$cells = [];
		foreach ($this->columns as $key => $value) {
			$content = '';
			if (isset($value['name'])) {
				$name = $value['name'];
				if (!isset($value['filter']) || $value['filter'] === true) {
					$inputOptions = [
						'type' => 'text',
						'class' => 'form-control input-sm',
						'name' => 'filter[' . $name . ']',
						'autocomplete' => 'off',
						'value' => isset($_GET['filter'][$name]) ? strip_tags($_GET['filter'][$name]) : ''
					];
					$content = Html::tag('input', $inputOptions);
				} else if (isset($value['filter']) && is_callable($value['filter'])) {
					$content = call_user_func($value['filter'], $value, [
						'name' => 'filter[' . $name . ']',
						'value' => isset($_GET['filter'][$name]) ? strip_tags($_GET['filter'][$name]) : ''
					]);
				}
			}
			$cells[] = Html::tag('td', null, $content);
		}

		return Html::tag('tr', null, implode('', $cells));

	}

	public function getSortActive($name, $mirror = false)
	{
		$activeUrl = UrlManager::getActiveUrl();
		$parseUrl = UrlManager::parse($activeUrl);
		if (!empty($parseUrl['query']['sort'][$name])) {
			if ($parseUrl['query']['sort'][$name] == 'ASC') {
				return $mirror ? 'DESC' : 'ASC';
			} else if ($parseUrl['query']['sort'][$name] == 'DESC') {
				return $mirror ? 'ASC' : 'DESC';
			} else {
				return false;
			}
		} else {
			return false;
		}

	}

	public function getOrderUrl($name, $active = false)
	{
		$activeUrl = UrlManager::getActiveUrl();
		$parseUrl = UrlManager::parse($activeUrl);

		if ($active === false) {
			$order = $this->getSortActive($name, true) === false ? 'DESC' : $this->getSortActive($name, true);
		} else {
			$order = $active === 'ASC' ? 'DESC' : 'ASC';
		}
		unset($parseUrl['query']['sort']);

		$qs = [
			'sort' => [
				$name => $order
			]
		];

		return $this->buildUrl($qs);

	}

	public function renderTableBody()
	{
		$rows = [];
		foreach ($this->dataProvider['records'] as $key => $value) {
			$rows[] = $this->renderBodyRow($value);
		}
		$tbody = Html::tag('tbody', null, implode('', $rows));
		$table = Html::tag('table', $this->table['htmlOptions'], $tbody);

		$bodyOptions = [];
		Html::addCssClass('body', $bodyOptions);
		if ($this->tableScrollVertical) {
			Html::addCssClass('body-overflow', $bodyOptions);
		}

		return Html::tag('div', $bodyOptions, $table);

	}

	public function renderBodyRow($records)
	{
		$cells = [];
		$htmlOptions = [];
		foreach ($this->columns as $key => $value) {
			$cells[] = $this->renderBodyCell($records, $value);
		}

		if (is_array($this->rowHtmlOptions)) {
			$htmlOptions = ValueFormatter::extend($htmlOptions, $this->rowHtmlOptions);
		} else if (is_callable($this->rowHtmlOptions)) {
			$result = call_user_func($this->rowHtmlOptions, $records);
			if (is_array($result)) {
				$htmlOptions = ValueFormatter::extend($htmlOptions, $result);
			}
		}

		return Html::tag('tr', $htmlOptions, implode('', $cells));

	}

	public function renderBodyCell($records, $options)
	{
		$value = '';
		$htmlOptions = [];
		$name = Html::getValue($options, 'name', '', false);
		if (isset($options['value']) && (is_string($options['value']) || is_numeric($options['value']))) {
			$value = $options['value'];
		} else if (isset($options['value']) && is_callable($options['value'])) {
			$value = call_user_func($options['value'], $records, $options, $this->dataProvider);
		} else {
			$value = $records[$name];
		}

		if (isset($options['htmlOptions'])) {
			$htmlOptions = ValueFormatter::extend($htmlOptions, $options['htmlOptions']);
		}

		return Html::tag('td', $htmlOptions, $value);

	}

	public function renderToolbar()
	{
		$pagination = $this->toolbar['pagination'] ? $this->paginatioObject : false;
		//$pageSummary = $this->pageSummaryString;

		$content = array_filter([$pagination]);

		return Html::tag('div', ['class' => 'toolbar'], implode('', $content));

	}

	public function renderPageSummary()
	{
		$page = $this->dataProvider['pagination']['currentPage'];
		$total = $this->dataProvider['pagination']['total'];
		if ($this->pagination['enabled'] === false) {
			return "Total <strong>{$total}</strong> items";
		} else {			
			$limit = $this->dataProvider['pagination']['pageSize'];
			$last = (int) ceil($total / $limit);
			$start = $page > 1 ? (($page - 1) * $limit) + 1 : 1;
			$end =  $page >= $last ? $total : $start + $limit - 1;
			return "Showing <strong>{$start} - {$end}</strong> of <strong>{$total}</strong> items.";
		}

	}

	public function renderPagination()
	{
		switch ($this->pagination['type']) {
			case self::PAGINATION_TYPE_NUMERIC:
				return $this->renderPaginationNumeric();
				break;
			case self::PAGINATION_TYPE_NEXTPREV:
				return $this->renderPaginationNextPrev();
				break;

			default:
				return;
				break;
		}

	}

	public function renderPaginationNumeric()
	{
		$buttons = [];

		$limit = $this->dataProvider['pagination']['pageSize'];
		$page = $this->dataProvider['pagination']['currentPage'];
		$total = $this->dataProvider['pagination']['total'];
		$center = $this->pagination['centerPosition'] - 1;
		$length = $center + $center + 1; //$center < 2 ? 3 : ($center + $center);

		$last = (int) ceil($total / $limit);
		$start = (($page - $center) > 0) ? $page - $center : 1;
		$end = (($page + $center) < $last) ? $page + $center : $last;


		if ($end < $length) {
			if ($last >= $length) {
				$end = $length;
			} else {
				$end = $last;
			}
		}

		if (($end - $start + 1) < $length) {
			$start = $last - $length + 1;
			if ($start < 1) {
				$start = 1;
			}
		}

		// prev page
		$buttons[] = $this->paginationButtonNumbers(1, '&laquo;');
		$buttons[] = $this->paginationButtonNumbers($page > 1 ? ($page - 1) : 1, '&lsaquo;');

		for ($i = $start; $i <= $end; $i++) {
			$buttons[] = $this->paginationButtonNumbers($i);
		}

		// next page
		$buttons[] = $this->paginationButtonNumbers($page < $last ? ($page + 1) : $last, '&rsaquo;');
		$buttons[] = $this->paginationButtonNumbers($last, '&raquo;');

		return Html::tag('ul', ['class' => 'pagination'], implode('', $buttons));

	}

	public function paginationButtonNumbers($page, $label = null)
	{
		$liOptions = [];
		$aOption = [];
		$text = $label === null ? $page : $label;

		if ($this->dataProvider['pagination']['currentPage'] == $page) {
			Html::addAttribute('href', 'javascript:;', $aOption);
			Html::addCssClass($label === null ? 'active' : 'disabled', $liOptions);
		} else {
			Html::addAttribute('data-pfs-sg-ajax-link', 'true', $aOption);
			Html::addAttribute('href', $this->getPageUrl($page), $aOption);
		}

		$link = Html::tag('a', $aOption, $text);
		return Html::tag('li', $liOptions, $link);

	}

	public function renderPaginationNextPrev()
	{
		$options = [];

		$limit = $this->dataProvider['pagination']['pageSize'];
		$page = $this->dataProvider['pagination']['currentPage'];
		$total = $this->dataProvider['pagination']['total'];
		$last = (int) ceil($total / $limit);

		$content = [];
		$buttons = [];
		$buttons[] = $this->paginationButtonNextPrev(1, '&laquo;');
		$buttons[] = $this->paginationButtonNextPrev($page > 1 ? ($page - 1) : 1, '&lsaquo;');
		$content[] = Html::tag('div', ['class' => 'input-group-btn'], implode('', $buttons));

		$inputOptions = [
			'value' => $page,
			'class' => 'form-control',
			'type' => 'text',
			'name' => 'current-page',
			'autocomplete' => 'off',
		];
		$content[] = Html::tag('input', $inputOptions);


		$buttons = [];
		$buttons[] = $this->paginationButtonNextPrev($page < $last ? ($page + 1) : $last, '&rsaquo;');
		$buttons[] = $this->paginationButtonNextPrev($last, '&raquo;');
		$content[] = Html::tag('div', ['class' => 'input-group-btn'], implode('', $buttons));

		Html::addAttribute('action', $this->buildUrl(), $options);
		Html::addAttribute('method', 'get', $options);
		Html::addAttribute('class', 'pagination', $options);
		Html::addAttribute('data-pfs-sg-ajax-pagination', 'true', $options);
		$inputGroup = Html::tag('div', ['class' => 'input-group input-group-sm'], implode('', $content));
		return Html::tag('form', $options, $inputGroup);

	}

	public function paginationButtonNextPrev($page, $label = null)
	{
		$aOption = [];
		$text = $label === null ? $page : $label;

		if ($this->dataProvider['pagination']['currentPage'] == $page) {
			Html::addAttribute('href', 'javascript:;', $aOption);
			Html::addCssClass('disabled', $aOption);
		} else {
			Html::addAttribute('data-pfs-sg-ajax-link', 'true', $aOption);
			Html::addAttribute('href', $this->getPageUrl($page), $aOption);
		}

		Html::addCssClass('btn btn-default', $aOption);
		return Html::tag('a', $aOption, $text);

	}

	public function getPageUrl($page)
	{
		$qs = ['current-page' => $page];
		return $this->buildUrl($qs);

	}

	public function buildUrl($qs = [])
	{
		if ($this->csrfToken !== null && $this->csrfTokenName !== null) {
			$qs[$this->csrfTokenName] = $this->csrfToken;
		}
		return UrlManager::route(UrlManager::getActiveUrl(), $qs);

	}
}