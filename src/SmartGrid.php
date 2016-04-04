<?php

namespace SmartGrid;

use SmartGrid\base\Widget;
use SmartGrid\helpers\Html;
use SmartGrid\helpers\JavaScript;
use SmartGrid\helpers\UrlManager;
use SmartGrid\helpers\ValueFormatter;

class SmartGrid extends Widget
{
    public $showHeader = true;
    public $showFooter = false;
    public $showPagination = true;
    public $showPanelOptionTop = true;
    public $panelOptionTop = [
        'pagination' => true
    ];
    public $paginationOptions = [
        'type' => 'nextprev',
        'center' => 3
    ];
    public $rowHtmlOptions = [];
    public $sort = true;
    public $filter = true;
    public $tableScrollY = true;
    public $selectableCell = true;
    public $htmlOptions = [];
    public $tableContainerHtmlOptions = [];
    public $tableContainerClass = 'pfs-sg-container';
    public $tableHtmlOptions = [];
    public $tableClass = 'table table-bordered';
    public $dataProvider = [];
    public $columns = [];

    public function run()
    {
        $this->dataProvider = $this->dataProvider->run();
        $this->columns = $this->formatColumns();
        Html::addCssId($this->id, $this->htmlOptions);
        $content = array_filter([
            $this->renderTable()
        ]);
        return Html::tag('div', $this->htmlOptions, implode('', $content));
    }

    public function formatColumns()
    {
        if (empty($this->columns)) {
            return [];
        } else {
            $data = [];
            foreach ($this->columns as $key => $value) {
                if (is_array($value)) {
                    /*
                    if (isset($value['name']) || isset($value['label'])) {
                        if (!isset($value['name'])) {
                            $value['name'] = $value['label'];
                        }
                        array_push($data, $value);
                    } else {
                        throw new \Exception('Name or Label required.');
                    } */
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

    public function renderTable()
    {
        // Table Container HtmlOptions
        Html::addCssClass($this->tableContainerClass, $this->tableContainerHtmlOptions);

        // Table HtmlOptions
        Html::addCssClass($this->tableClass, $this->tableHtmlOptions);

        $panelOptionTop = $this->showPanelOptionTop ? $this->renderPanelTop() : false;
        $tableHeader = $this->showHeader ? $this->renderTableHeader() : false;
        $tableBody = $this->renderTableBody();
        $content = [
            $panelOptionTop,
            $tableHeader,
            $tableBody
        ];
        return Html::tag('div', $this->tableContainerHtmlOptions, implode('', $content));
    }

    public function renderTableHeader()
    {
        $cells = [];
        $no = 0;
        foreach ($this->columns as $key => $value) {
            $no++;
            $cells[] = $this->renderHeaderCell($value, $no);
        }
        $tr = [];
        $tr[] = Html::tag('tr', [], implode('', $cells));

        if ($this->filter === true) {
            $tr[] = $this->renderHeaderFilter();
        }

        $thead = Html::tag('thead', [], implode('', $tr));
        $table = Html::tag('table', $this->tableHtmlOptions, $thead);

        if ($this->filter === true) {

            $formOptions = [
                'action' => UrlManager::getActiveUrl(),
                'method' => 'GET',
                'data-pfs-ajax-filter' => 'true'
            ];
            $submit = Html::tag('button', ['style' => 'display: none', 'type' => 'submit'], '');
            $form = Html::tag('form', $formOptions, $submit.$table);
            return Html::tag('div', ['class'=>'header'], $form);
        }

        $containerHeader = Html::tag('div', ['class'=>'header'], $table);
        return $containerHeader;
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
                        'name' => 'filter['. $name .']',
                        'autocomplete' => 'off',
                        'value' => isset($_GET['filter'][$name]) ? strip_tags($_GET['filter'][$name]) : ''
                    ];
                    $content = Html::tag('input', $inputOptions);
                } else if (isset($value['filter']) && is_callable($value['filter'])) {
                    $filter = $value['filter']; // function
                    $content = $filter($value, [
                        'name' => 'filter['. $name .']',
                        'value' => isset($_GET['filter'][$name]) ? strip_tags($_GET['filter'][$name]) : ''
                    ]);
                }
            }
            $cells[] = Html::tag('td', [], $content);
        }

        return Html::tag('tr', [], implode('', $cells));
    }

    public function renderHeaderCell($options, $no)
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
                    
            Html::addAttribute('data-pfs-ajax-link', 'true', $linkOption);
            Html::addAttribute('class', 'icon-sort', $linkOption);
            Html::addAttribute('class', 'icon-sort-'. $iconName, $linkOption);
            $label = Html::tag('a', $linkOption, $label);
        }

        $thOptions = [];
        if (isset($options['headerHtmlOptions'])) {
            $thOptions = ValueFormatter::extend($thOptions, $options['headerHtmlOptions']);
        }
        Html::addCssId($this->id .'_header_'. $name, $thOptions);
        Html::addAttribute('data-pfs-title-position', $no, $thOptions);
        return Html::tag('th', $thOptions, $label);
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
        return UrlManager::route(UrlManager::build($parseUrl), [
            'sort' => [
                $name => $order
            ]
        ]);
       
    }

    public function renderTableBody()
    {
        $rows = [];
        foreach($this->dataProvider['records'] as $key => $value) {
            $rows[] = $this->renderBodyRow($value);
        }
        $tbody = Html::tag('tbody', [], implode('', $rows));
        $table = Html::tag('table', $this->tableHtmlOptions, $tbody);
        
        $bodyOptions = [];
        Html::addCssClass('body', $bodyOptions);
        if ($this->tableScrollY) {
            Html::addCssClass('body-overflow', $bodyOptions);
        }

        return Html::tag('div', $bodyOptions, $table);
    }

    public function renderBodyRow($records)
    {
        $cells = [];
        $htmlOptions = [];
        foreach($this->columns as $key => $value) {
            $cells[] = $this->renderBodyCell($records, $value);
        }

        if (is_array($this->rowHtmlOptions)) {
            $htmlOptions = ValueFormatter::extend($htmlOptions, $this->rowHtmlOptions);
        } else if (is_callable($this->rowHtmlOptions)) {
            $function = $this->rowHtmlOptions;
            $result = $function($records);
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
            $function = $options['value'];
            $value = $function($records, $options, $this->dataProvider);
        } else {
            $value = $records[$name];
        }

        if (isset($options['htmlOptions'])) {
            $htmlOptions = ValueFormatter::extend($htmlOptions, $options['htmlOptions']);
        }

        return Html::tag('td', $htmlOptions, $value);
    }

    public function renderPanelTop()
    {
        $pagination = $this->panelOptionTop['pagination'] ? $this->renderPagination() : false;
        $content = array_filter([
            $pagination
        ]);
        $td = Html::tag('td', [], implode('', $content));
        $tr = Html::tag('tr', [], $td);
        $thead = Html::tag('thead', [], $tr);
        $table = Html::tag('table', $this->tableHtmlOptions, $thead);
        return Html::tag('div', ['class'=>'panel-option-top'], $table);
    }

    public function renderPagination()
    {
        switch ($this->paginationOptions['type']) {
            case 'numeric':
                return $this->renderPaginationNumeric();
                break;
            case 'nextprev': 
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
        $center = $this->paginationOptions['center'] - 1;
        $length = $center + $center + 1;//$center < 2 ? 3 : ($center + $center);
        
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
        $buttons[] = $this->paginationButtonNumbers(1, 'fa fa-angle-double-left');
        $buttons[] = $this->paginationButtonNumbers($page > 1 ? ($page - 1) : 1, 'fa fa-angle-left');

        for($i = $start; $i <= $end; $i++) {
            $buttons[] = $this->paginationButtonNumbers($i);
        }

        // next page
        $buttons[] = $this->paginationButtonNumbers($page < $last ? ($page + 1) : $last, 'fa fa-angle-right');
        $buttons[] = $this->paginationButtonNumbers($last, 'fa fa-angle-double-right');

        return Html::tag('ul', ['class'=>'pagination'], implode('', $buttons));
    }

    public function paginationButtonNumbers($page, $icon = null)
    {
        $liOptions = [];
        $aOption = [];
        $text = $page;
        if ($icon) {
            $text = Html::fontAwesome($icon);
        }

        if ($this->dataProvider['pagination']['currentPage'] == $page) {
            Html::addAttribute('href', 'javascript:;', $aOption);
            Html::addCssClass($icon == null ? 'active' : 'disabled', $liOptions);
        } else {
            Html::addAttribute('data-pfs-ajax-link', 'true', $aOption);
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
        $buttons[] = $this->paginationButtonNextPrev(1, 'fa fa-angle-double-left');
        $buttons[] = $this->paginationButtonNextPrev($page > 1 ? ($page - 1) : 1, 'fa fa-angle-left');
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
        $buttons[] = $this->paginationButtonNextPrev($page < $last ? ($page + 1) : $last, 'fa fa-angle-right');
        $buttons[] = $this->paginationButtonNextPrev($last, 'fa fa-angle-double-right');
        $content[] = Html::tag('div', ['class' => 'input-group-btn'], implode('', $buttons));

        Html::addAttribute('action', UrlManager::getActiveUrl(), $options);
        Html::addAttribute('method', 'get', $options);
        Html::addAttribute('class', 'pagination', $options);
        Html::addAttribute('data-pfs-ajax-pagination', 'true', $options);
        $inputGroup = Html::tag('div', ['class' => 'input-group input-group-sm'], implode('', $content));
        return Html::tag('form', $options, $inputGroup);

    }

    public function paginationButtonNextPrev($page, $icon = null)
    {
        $aOption = [];
        $text = $page;
        if ($icon) {
            $text = Html::fontAwesome($icon);
        }

        if ($this->dataProvider['pagination']['currentPage'] == $page) {
            Html::addAttribute('href', 'javascript:;', $aOption);
            Html::addCssClass('disabled', $aOption);
        } else {
            Html::addAttribute('data-pfs-ajax-link', 'true', $aOption);
            Html::addAttribute('href', $this->getPageUrl($page), $aOption);
        }

        Html::addCssClass('btn btn-default', $aOption);
        return Html::tag('a', $aOption, $text);
    }

    public function getPageUrl($page) 
    {
        return UrlManager::route(UrlManager::getActiveUrl(), ['current-page' => $page]); 
    }
}