<?php

class Admin_DbController extends Zend_Controller_Action
{

	public function init()
	{
		parent::init();
		/* Initialize action controller here */
		$this->_helper->layout->disableLayout();
		//$this->_helper->viewRenderer->setNoRender();
		if (!App_Memcached::get('tables', $tables))
		{
			$tables = HBase_Table::getTableNames();
			App_Memcached::set('tables', $tables, 30);
		}
		$result = array();
		foreach ($tables as $table)
		{
			if (!App_Memcached::get('table_cfs_' . $table, $columnFamilies))
			{
				$columnFamilies = HBase_Table::getColumnDescriptors($table);
				App_Memcached::add('table_cfs_' . $table, $columnFamilies);
			}
			$result[$table] = array_keys($columnFamilies);
		}
		$this->view->tables = $result;
	}

	public function indexAction()
	{
	}

	public function tableAction()
	{
		$table = $this->_getParam('name');
		$cf = $this->_getParam('cf');
		$onlyIds = $this->_getParam('only');
		$rowId = $this->_getParam('id', '');
		$num_by_page = $this->_getParam('num', 10);
		$this->view->headTitle()->prepend($table);
		$this->view->cf = $this->view->tables[$table];
		$this->view->table = $table;
		$this->view->current_cf = $cf;
		$this->view->rowId = $rowId;

		Model_Admins::setTable($table);
		$result = array();
		if (isset($cf))
		{
			$scanner = Model_Admins::scannerOpenWithPrefix($rowId, array($cf));
		}
		else
		{
			$scanner = Model_Admins::scannerOpenWithPrefix($rowId, array());
		}
		$rows = array();
		while ($columns = Model_Admins::scannerGetList($scanner, $num_by_page))
		{
			foreach ($columns as $column)
			{
				foreach ($column->columns as $nameOrig => $cell)
				{
					$strpos = strpos($nameOrig, ':') + 1;

					$family = substr($nameOrig, 0, $strpos);
					$columnName = substr($nameOrig, $strpos);
					$result[$column->row][$family][$columnName] = $cell->value;
					$rows[$column->row][$family][$columnName] = $cell;
				}
			}
			break;
		}
		$this->view->rows = $rows;
		Model_Admins::scannerClose($scanner);
		$this->view->result = $result;
		if (isset($onlyIds))
		{
			$this->render('ids');
		}
	}

	public function tableAjaxAction()
	{
		$table = $this->_getParam('table');
		$cf = $this->_getParam('cf');
		$num_by_page = $this->_getParam('num', 10);

		if (empty($cf))
		{
			$cf = null;
		}
		$from_scanner = $this->_getParam('last_row_id', '');

		$this->view->headTitle()->prepend($table);
		$this->view->cf = array_keys(HBase_Table::getColumnDescriptors($table));
		$this->view->table = $table;
		$this->view->currentCf = $cf;
		$this->view->rowId = '';

		Model_Admins::setTable($table);
		$result = array();
		if (isset($cf))
		{
			$scanner = Model_Admins::scannerOpen($from_scanner, array($cf));
		}
		else
		{
			$scanner = Model_Admins::scannerOpen($from_scanner, array());
		}
		$rows = array();
		while ($columns = Model_Admins::scannerGetList($scanner, $num_by_page))
		{
			foreach ($columns as $column)
			{
				if ($column->row == $from_scanner)
				{
					continue;
				}
				foreach ($column->columns as $nameOrig => $cell)
				{
					$strpos = strpos($nameOrig, ':') + 1;

					$family = substr($nameOrig, 0, $strpos);
					$columnName = substr($nameOrig, $strpos);
					$result[$column->row][$family][$columnName] = $cell->value;
					$rows[$column->row][$family][$columnName] = $cell;
				}
			}
			break;
		}
		$this->view->rows = $rows;
		$this->view->last_row_id = $column->row;
		Model_Admins::scannerClose($scanner);
		$this->view->result = $result;
	}

	public function rowAction()
	{
		$table = $this->_getParam('table');
		$cf = $this->_getParam('cf');
		$rowId = $this->_getParam('id');

		$this->view->table = $table;
		Model_Admins::setTable($table);
		$result = array();
		if (isset($cf))
		{
			$row = Model_Admins::getRowWithColumns($rowId, array($cf));
		}
		else
		{
			$row = Model_Admins::getRow($rowId);
		}
		foreach ($row[0]->columns as $nameOrig => $cell)
		{

			$name = explode(':', $nameOrig);
			$family = $name[0] . ':';
			$columnName = $name[1];
			$result[$row[0]->row][$family][$columnName] = $cell->value;
			$rows[$row[0]->row][$family][$columnName] = $cell;
		}
		$this->view->rows = $rows;
		$this->view->cf = array();
		$this->view->result = $result;
		$this->view->row = true;
		$this->view->rowId = $rowId;
		$this->view->currentCf = $cf;
		$this->render('table');
	}

	public function editAction()
	{
		$params = $this->getAllParams();
		$table = $params['table'];
		$this->view->table = $table;
		$row = $this->view->row = $params['row'];
		Model_Admins::setTable($table);
		$column = Model_Admins::get($params['row'], "$params[cf]$params[cn]");
		$populate['cn'] = $params['cn'];
		$populate['cf'] = $params['cf'];
		$populate['value'] = $column[0]->value;
		$this->view->url = $row . $params['cf'] . $params['cn'];
		$form = $this->formEdit($table);
		$form->populate($populate);
		if ($this->getRequest()->isPost())
		{
			if ($form->isValid($_POST))
			{
				$values = $form->getValues();
				$cf = '';
				$cn = '';
				$value = '';
				extract($values);
				if ($values['cn'] != $populate['cn'] || $values['cf'] != $populate['cf'])
				{
					$mutations[] = new Mutation(array(
																					 'column' => $populate['cf'] . $populate['cn'],
																					 'isDelete' => true,
																			));
					$mutations[] = new Mutation(array(
																					 'column' => $cf . $cn,
																					 'value' => $value,
																			));
					Model_Admins::mutateRow($row, $mutations);
					$columnNew = Model_Admins::get($row, $cf . $cn);
					echo '<p>old:' . $populate['cn'] . print_r($column, true) . '</p>';
					echo '<p>new:' . $cn . print_r($columnNew, true) . '</p>';
					//$this->redirect('/admins/table/name/'.$table.'#'.$row.$cf);
					echo 'edited cf or cn';
					echo '<p>старая колонка была удалена, удалить кэш? </p>';
					echo "<p><a href='/admins/cache/table/user/row/$row/cf/$populate[cf]/cn/$populate[cn]'> Очистить кэш </a></p>";
					echo 'edit value?<br/>';
					echo "<a href='/admins/edit/table/$table/row/$row/cf/$cf/cn/$cn'>edit</a>";

				}
				elseif ($values['value'] != $populate['value'])
				{
					$mutations[] = new Mutation(array(
																					 'column' => $cf . $cn,
																					 'value' => $value,
																			));
					Model_Admins::mutateRow($row, $mutations);
					$columnNew = Model_Admins::get($row, $cf . $cn);
					print_r($columnNew);
					echo 'edited value';
				}
				else
				{
					echo 'no changes';
				}
			}
		}
		$this->view->form = $form;
	}

	public function addAction()
	{
		$params = $this->getAllParams();
		$table = $params['table'];
		$this->view->table = $table;
		$row = $this->view->row = $params['row'];
		Model_Admins::setTable($table);
		$populate['cf'] = $params['cf'];
		$this->view->url = $row . $params['cf'];
		$form = $this->formEdit($table, ' добавить ');
		$form->populate($populate);
		if ($this->getRequest()->isPost())
		{
			if ($form->isValid($_POST))
			{
				$values = $form->getValues();
				$cf = '';
				$cn = '';
				$value = '';
				extract($values);
				{
					$mutations[] = new Mutation(array(
																					 'column' => $cf . $cn,
																					 'value' => $value,
																			));
					Model_Admins::mutateRow($row, $mutations);
					$columnNew = Model_Admins::get($row, $cf . $cn);
					print_r($columnNew);
					echo 'added value<br/>';
					echo 'edit value?<br/>';
					echo "<a href='/admins/edit/table/$table/row/$row/cf/$cf/cn/$cn'>edit</a>";
				}
			}
		}
		$this->view->form = $form;
	}

	public function deleteAction()
	{
		$table = '';
		$cf = '';
		$cn = '';
		$row = '';
		extract($this->getAllParams());
		Model_Admins::setTable($table);
		Model_Admins::getCacheKey($cf, $row);
		Model_Admins::deleteAll($row, $cf . $cn);
		if ($this->getRequest()->isXmlHttpRequest())
		{
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
			echo 'true';
			return true;
		}
		$this->redirect($_SERVER['HTTP_REFERER'] . '#' . $row . $cf);
	}

	public function deleteRowAction()
	{
		$table = '';
		$cf = '';
		$cn = '';
		$row = '';
		extract($this->getAllParams());
		Model_Admins::setTable($table);
		Model_Admins::getCacheKey($cf, $row);
		Model_Admins::deleteAllRow($row);
		if ($this->getRequest()->isXmlHttpRequest())
		{
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
			echo 'true';
			return true;
		}
		$this->_helper->viewRenderer->setNoRender();

		$this->redirect($_SERVER['HTTP_REFERER'] . '#' . $row . $cf);
	}

	public function cacheAction()
	{
		$table = '';
		$cf = '';
		$cn = '';
		$row = '';
		extract($this->getAllParams());
		Model_Admins::setTable($table);
		$key = Model_Admins::getCacheKey($cf, $row);
		$succ = App_Memcached::delete($key);
		if ($this->getRequest()->isXmlHttpRequest())
		{
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
			if ($succ)
			{
				echo 'true';
			}
			else
			{
				echo App_Memcached::getResultMessage();
			}
			return true;
		}
		$this->redirect($_SERVER['HTTP_REFERER'] . '#' . $row . $cf);
	}

	public function clearCacheAction()
	{
		$status = App_Memcached::flush();
		if ($this->getRequest()->isXmlHttpRequest())
		{
			$this->_helper->json(array('status' => $status));
		}
		else
		{
			$this->redirect($_SERVER['HTTP_REFERER']);
		}
	}

	static public function rec(array &$source, array $toAdd)
	{
		foreach ($toAdd as $key => $value)
		{
			if (is_array($value))
			{
				if (isset($source[$key]))
				{
					self::rec($source[$key], $value);
				}
				else
				{
					$source[$key] = $value;
				}
			}
			else
			{
				$source[$key] = $value;
			}
		}
	}

	private function formEdit($table, $submitText = ' Редактировать ')
	{
		$columnFamilies = array_keys(HBase_Table::getColumnDescriptors($table));
		foreach ($columnFamilies as $key)
		{
			$columnFamilies2[$key] = $key;
		}
		$form = new Zend_Form('Edit');
		$form->setAction('')
				->setMethod('post');
		$form->addElements(array(
														new Zend_Form_Element_Text('cn', array(
																																	'required' => true,
																																	'label' => 'Column Name',
																														 )),
														new Zend_Form_Element_Select('cf', array(
																																		'required' => true,
																																		'label' => 'Column Family',
																																		'multiOptions' => $columnFamilies2,
																																		'decorators' => array(
																																			array('ViewHelper'),
																																			array('Errors'),
																																			array('HtmlTag', array('tag' => 'dd')),
																																			array('Label', array('tag' => 'dt')),
																																		)
																															 )),
														new Zend_Form_Element_Textarea('value', array(
																																				 'required' => false,
																																				 'label' => 'Значение',
																																		)),
														new Zend_Form_Element_Submit($submitText, array(
																																					 'decorators' => array(
																																						 array('ViewHelper'),
																																						 //array('HtmlTag', array('tag' => 'center')),
																																					 )
																																			))
											 ));
		return $form;
	}

	public function getCacheKeyAction()
	{
		$key = $this->_getParam('id');
		$this->view->cacheKey = $key;
	}

	public function getCacheKeyAjaxAction()
	{
		$table = $this->_getParam('table');
		$cf = $this->_getParam('column_family');
		$rowId = $this->_getParam('row_id');
		Model_Admins::setTable($table);
		$key = Model_Admins::getCacheKey($cf, $rowId);
		$token = 0.0;
		$status = App_Memcached::getWithToken($key, $value, $token);
		if ($this->getRequest()->isXmlHttpRequest())
		{
			$this->_helper->json(array(
																'key' => $key,
																'status' => $status,
																'token' => $token,
																'value' => Zend_Debug::dump($value, null, false)
													 ));
		}
	}

	public function deleteCacheKeyAction()
	{
		$key = $this->_getParam('id');
		$status = App_Memcached::delete($key);
		if ($this->getRequest()->isXmlHttpRequest())
		{
			$this->_helper->json(array(
																'key' => $key,
																'status' => $status,
													 ));
		}
	}
}