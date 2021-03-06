<?php
/**
* @version 			SEBLOD 3.x Core ~ $Id: joomla_article.php sebastienheraud $
* @package			SEBLOD (App Builder & CCK) // SEBLOD nano (Form Builder)
* @url				http://www.seblod.com
* @editor			Octopoos - www.octopoos.com
* @copyright		Copyright (C) 2013 SEBLOD. All Rights Reserved.
* @license 			GNU General Public License version 2 or later; see _LICENSE.php
**/

defined( '_JEXEC' ) or die;

JLoader::register( 'JTableContent', JPATH_PLATFORM.'/joomla/database/table/content.php' );

// Plugin
class plgCCK_Storage_LocationJoomla_Article extends JCckPluginLocation
{
	protected static $type			=	'joomla_article';
	protected static $table			=	'#__content';
	protected static $table_object	=	array( 'Content', 'JTable' );
	protected static $key			=	'id';
	
	protected static $access		=	'access';
	protected static $author		=	'created_by';
	protected static $created_at	=	'created';
	protected static $custom		=	'introtext';
	protected static $modified_at	=	'modified';
	protected static $parent		=	'catid';
	protected static $parent_object	=	'joomla_category';
	protected static $status		=	'state';
	protected static $to_route		=	'a.id as pk, a.title, a.alias, a.catid, a.language';
	
	protected static $context		=	'com_content.article';
	protected static $contexts		=	array( 'com_content.article' );
	protected static $error			=	false;
	protected static $ordering		=	array( 'alpha'=>'title ASC', 'newest'=>'created DESC', 'oldest'=>'created ASC', 'ordering'=>'ordering ASC', 'popular'=>'hits DESC' );
	protected static $pk			=	0;
	protected static $sef			=	array( '1'=>'full',
											   '2'=>'full', '22'=>'id', '23'=>'alias', '24'=>'alias',
											   '3'=>'full', '32'=>'id', '33'=>'alias',
											   '4'=>'full', '42'=>'id', '43'=>'alias'
										);
	protected static $routes		=	array();

	// -------- -------- -------- -------- -------- -------- -------- -------- // Construct
	
	// onCCK_Storage_LocationConstruct
	public function onCCK_Storage_LocationConstruct( $type, &$data = array() )
	{
		if ( self::$type != $type ) {
			return;
		}
		if ( empty( $data['storage_table'] ) ) {
			$data['storage_table']	=	self::$table;
		}
		$data['core_table']		=	self::$table;
		$data['core_columns']	=	array( 'associations', 'tags' );
	}
	
	// -------- -------- -------- -------- -------- -------- -------- -------- // Prepare
	
	// onCCK_Storage_LocationPrepareContent
	public function onCCK_Storage_LocationPrepareContent( &$field, &$storage, $pk = 0, &$config = array(), &$row = null )
	{
		if ( self::$type != $field->storage_location ) {
			return;
		}
		
		// Init
		$table	=	$field->storage_table;
		
		// Set
		if ( $table == self::$table ) {
			$storage			=	self::_getTable( $pk, true );
			$storage->slug		=	( $storage->alias ) ? $storage->id.':'.$storage->alias : $storage->id;
			$config['author']	=	$storage->{self::$author};
		} else {
			$storage			=	parent::g_onCCK_Storage_LocationPrepareContent( $table, $pk );
			if ( ! isset( $config['storages'][self::$table] ) ) {
				$config['storages'][self::$table]		=	self::_getTable( $pk, true );
				$config['storages'][self::$table]->slug	=	( $config['storages'][self::$table]->alias ) ? $config['storages'][self::$table]->id.':'.$config['storages'][self::$table]->alias
																										 : $config['storages'][self::$table]->id;
				$config['author']						=	$config['storages'][self::$table]->{self::$author};
			}
		}
		if ( $config['doSEF'] && isset( $row->readmore_link ) ) {
			$row->readmore_link	=	self::getRouteByStorage( $config['storages'], $config['doSEF'], $config['Itemid'], $config );
			$config['doSEF']	=	0;
		}
	}
	
	// onCCK_Storage_LocationPrepareDelete
	public function onCCK_Storage_LocationPrepareDelete( &$field, &$storage, $pk = 0, &$config = array() )
	{
		if ( self::$type != $field->storage_location ) {
			return;
		}
		
		// Init
		$table	=	$field->storage_table;
		
		// Set
		if ( $table == self::$table ) {
			$storage	=	self::_getTable( $pk );
		} else {
			$storage	=	parent::g_onCCK_Storage_LocationPrepareForm( $table, $pk );
		}
	}

	// onCCK_Storage_LocationPrepareForm
	public function onCCK_Storage_LocationPrepareForm( &$field, &$storage, $pk = 0, &$config = array() )
	{
		if ( self::$type != $field->storage_location ) {
			return;
		}
		
		// Init
		$table	=	$field->storage_table;
		if ( isset( $config['primary'] ) && $config['primary'] != self::$type ) {
			$pk	=	$config['pkb'];
		}
		
		// Set
		if ( $table == self::$table ) {
			$storage			=	self::_getTable( $pk );
			$config['asset']	=	'com_content';
			if ( $config['translate_id'] ) {
				$empty						=	array( self::$key, 'alias', 'created', 'created_by', 'hits', 'modified', 'modified_by', 'version' );
				$config['language']			=	JFactory::getApplication()->input->get( 'translate' );
				$config['translate']		=	$storage->language;
				$config['translated_id']	=	$config['translate_id'].':'.$storage->alias;
				foreach ( $empty as $k ) {
					$storage->$k	=	'';
				}
			} else {
				$config['asset_id']	=	(int)$storage->asset_id;
				$config['author']	=	$storage->{self::$author};
				$config['custom']	=	( ! $config['custom'] ) ? self::$custom : $config['custom'];
				$config['language']	=	$storage->language;
			}
		} else {
			$storage			=	parent::g_onCCK_Storage_LocationPrepareForm( $table, $pk );
		}
	}
	
	// onCCK_Storage_LocationPrepareItems
	public function onCCK_Storage_LocationPrepareItems( &$field, &$storages, $pks, &$config = array(), $load = false )
	{
		if ( self::$type != $field->storage_location ) {
			return;
		}
		
		// Init
		$table	=	$field->storage_table;
		
		// Prepare
		if ( $load ) {
			if ( $table == self::$table ) {
				$storages[$table]	=	JCckDatabase::loadObjectList( 'SELECT a.*, b.title AS category_title, b.alias AS category_alias'
																	. ' FROM '.$table.' AS a LEFT JOIN #__categories AS b ON b.id = a.catid'
																	. ' WHERE a.'.self::$key.' IN ('.$config['pks'].')', self::$key );
				foreach ( $storages[self::$table] as $s ) {
					$s->slug		=	( $s->alias ) ? $s->id.':'.$s->alias : $s->id;
				}
			} else {
				$storages[$table]	=	JCckDatabase::loadObjectList( 'SELECT * FROM '.$table.' WHERE id IN ('.$config['pks'].')', 'id' );
				if ( !isset( $storages[self::$table] ) ) {
					$storages['_']			=	self::$table;
					$storages[self::$table]	=	JCckDatabase::loadObjectList( 'SELECT a.*, b.title AS category_title, b.alias AS category_alias'
																			. ' FROM '.self::$table.' AS a LEFT JOIN #__categories AS b ON b.id = a.catid'
																			. ' WHERE a.'.self::$key.' IN ('.$config['pks'].')', self::$key );
					foreach ( $storages[self::$table] as $s ) {
						$s->slug	=	( $s->alias ) ? $s->id.':'.$s->alias : $s->id;
					}
				}
			}
		}
		$config['author']	=	(int)$storages[self::$table][$config['pk']]->{self::$author};
	}
	
	// onCCK_Storage_LocationPrepareList
	public static function onCCK_Storage_LocationPrepareList( &$params )
	{
		require_once JPATH_SITE.'/components/com_content/helpers/route.php';
		require_once JPATH_SITE.'/components/com_content/router.php';
		
		JPluginHelper::importPlugin( 'content' );
		$params	=	JComponentHelper::getParams( 'com_content' );
	}
	
	// onCCK_Storage_LocationPrepareOrder
	public function onCCK_Storage_LocationPrepareOrder( $type, &$order, &$tables, &$config = array() )
	{
		if ( self::$type != $type ) {
			return;
		}
		
		$order	=	( isset( self::$ordering[$order] ) ) ? $tables[self::$table]['_'] .'.'. self::$ordering[$order] : '';
	}
	
	// onCCK_Storage_LocationPrepareSearch
	public function onCCK_Storage_LocationPrepareSearch( $type, &$query, &$tables, &$t, &$config = array(), &$inherit = array(), $user )
	{
		if ( self::$type != $type ) {
			return;
		}
		
		// Init
		$db		=	JFactory::getDbo();
		$null	=	$db->getNullDate();
		$now	=	JFactory::getDate()->toSql();
		
		// Prepare
		if ( ! isset( $tables[self::$table] ) ) {
			$tables[self::$table]	=	array( '_'=>'t'.$t++,
											   'fields'=>array(),
											   'join'=>1,
											   'location'=>self::$type
										);
		}
		
		// Set
		$t_pk	=	$tables[self::$table]['_'];
		if ( ! isset( $tables[self::$table]['fields']['state'] ) ) {
			$query->where( $t_pk.'.state = 1' );
		}
		if ( ! isset( $tables[self::$table]['fields']['access'] ) ) {
			$access	=	implode( ',', $user->getAuthorisedViewLevels() );
			$query->where( $t_pk.'.access IN ('.$access.')' );
		}
		if ( ! isset( $tables[self::$table]['fields']['publish_up'] ) ) {
			$query->where( '( '.$t_pk.'.publish_up = '.$db->quote( $null ).' OR '.$t_pk.'.publish_up <= '.$db->quote( $now ).' )' );
		}
		if ( ! isset( $tables[self::$table]['fields']['publish_down'] ) ) {
			$query->where( '( '.$t_pk.'.publish_down = '.$db->quote( $null ).' OR '.$t_pk.'.publish_down >= '.$db->quote( $now ).' )' );
		}
	}
	
	// -------- -------- -------- -------- -------- -------- -------- -------- // Store
	
	// onCCK_Storage_LocationDelete
	public static function onCCK_Storage_LocationDelete( $pk, &$config = array() )
	{
		$app		=	JFactory::getApplication();
		$dispatcher	=	JDispatcher::getInstance();
		$table		=	self::_getTable( $pk );	
		
		if ( !$table ) {
			return false;
		}
		
		// Check
		$user 			=	JCck::getUser();
		$canDelete		=	$user->authorise( 'core.delete', 'com_cck.form.'.$config['type_id'] );
		$canDeleteOwn	=	$user->authorise( 'core.delete.own', 'com_cck.form.'.$config['type_id'] );
		if ( ( !$canDelete && !$canDeleteOwn ) ||
			 ( !$canDelete && $canDeleteOwn && $config['author'] != $user->get( 'id' ) ) ||
			 ( $canDelete && !$canDeleteOwn && $config['author'] == $user->get( 'id' ) ) ) {
			$app->enqueueMessage( JText::_( 'COM_CCK_ERROR_DELETE_NOT_PERMITTED' ), 'error' );
			return;
		}
		
		// Process
		$result	=	$dispatcher->trigger( 'onContentBeforeDelete', array( self::$context, $table ) );
		if ( in_array( false, $result, true ) ) {
			return false;
		}
		if ( !$table->delete( $pk ) ) {
			return false;
		}
		$dispatcher->trigger( 'onContentAfterDelete', array( self::$context, $table ) );
		
		return true;
	}
	
	// onCCK_Storage_LocationStore
	public function onCCK_Storage_LocationStore( $type, $data, &$config = array(), $pk = 0 )
	{
		if ( self::$type != $type ) {
			return;
		}
		
		if ( isset( $config['primary'] ) && $config['primary'] != self::$type ) {
			return;
		}
		if ( ! @$config['storages'][self::$table]['_']->pk ) {
			self::_core( $config['storages'][self::$table], $config, $pk );
			$config['storages'][self::$table]['_']->pk	=	self::$pk;
		}
		if ( $data['_']->table != self::$table ) {
			parent::g_onCCK_Storage_LocationStore( $data, self::$table, self::$pk, $config );
		}
		
		return self::$pk;
	}
	
	// -------- -------- -------- -------- -------- -------- -------- -------- // Protected
	
	// _core
	protected function _core( $data, &$config = array(), $pk = 0 )
	{
		if ( ! $config['id'] ) {
			$isNew	=	true;
			$config['id']	=	parent::g_onCCK_Storage_LocationPrepareStore();
		} else {
			$isNew	=	false;
		}
		
		// Init
		$app	=	JFactory::getApplication();
		$table	=	self::_getTable( $pk );
		$isNew	=	( $pk > 0 ) ? false : true;
		if ( isset( $table->tags ) ) {
			$tags	=	$table->tags;
			unset( $table->tags );
		} else {
			$tags	=	null;
		}
		if ( isset( $data['tags'] ) ) {
			if ( !empty( $data['tags'] ) && $data['tags'][0] != '' ) {
				$table->newTags	=	$data['tags'];
			}
			unset( $data['tags'] );
		}
		self::_initTable( $table, $data, $config );
		
		// Check Error
		if ( self::$error === true ) {
			return false;
		}
		
		// Prepare
		if ( is_array( $data ) ) {
			if ( $config['task'] == 'save2copy' ) {
				$empty		=	array( self::$key, 'alias', 'created', 'created_by', 'hits', 'modified', 'modified_by', 'version' );
				foreach ( $empty as $k ) {
					$data[$k]	=	'';
				}
			}
			$table->bind( $data );
		}
		if ( $isNew && !isset( $data['rules'] ) ) {
			$data['rules']	=	array(
									'core.delete'=>array(),
									'core.edit'=>array(),
									'core.edit.state'=>array()
								);
		}
		if ( isset( $data['rules'] ) && $data['rules'] ) {
			if ( !is_array( $data['rules'] ) ) {
				$data['rules']	=	json_decode( $data['rules'] );
			}
			$rules	=	new JAccessRules( JCckDevHelper::getRules( $data['rules'] ) );
			$table->setRules( $rules );
		}
		$table->check();
		self::_completeTable( $table, $data, $config );
		
		// Store
		$dispatcher	=	JDispatcher::getInstance();
		JPluginHelper::importPlugin( 'content' );
		$dispatcher->trigger( 'onContentBeforeSave', array( self::$context, &$table, $isNew ) );
		if ( $isNew === true && parent::g_isMax( $table->{self::$author}, $table->{self::$parent}, $config ) ) {
			return;
		}
		
		if ( !$table->store() ) {
			JFactory::getApplication()->enqueueMessage( $table->getError(), 'error' );
			if ( $isNew ) {
				parent::g_onCCK_Storage_LocationRollback( $config['id'] );
			}
			return false;
		}
		
		// Featured
		self::_setFeatured( $table, $isNew );
		
		// Checkin
		parent::g_checkIn( $table );
		
		self::$pk			=	$table->{self::$key};
		if ( !$config['pk'] ) {
			$config['pk']	=	self::$pk;
		}
		
		$config['author']	=	$table->{self::$author};
		$config['parent']	=	$table->{self::$parent};
		
		parent::g_onCCK_Storage_LocationStore( $data, self::$table, self::$pk, $config );
		$dispatcher->trigger( 'onContentAfterSave', array( self::$context, &$table, $isNew ) );

		// Associations
		if ( JCckDevHelper::hasLanguageAssociations() ) {
			self::_setAssociations( $table, $data, $isNew, $config );
		}
	}
	
	// _getTable
	protected static function _getTable( $pk = 0, $join = false )
	{
		$table	=	JTable::getInstance( 'content' );
		
		if ( $pk > 0 ) {
			$table->load( $pk );
			if ( $table->id ) {
				if ( $join ) { // todo:join
					$join					=	JCckDatabase::loadObject( 'SELECT a.title, a.alias FROM #__categories AS a WHERE a.id = '.$table->catid );	//@
					if ( is_object( $join ) && isset( $join->title ) ) {
						$table->category_title	=	$join->title;
						$table->category_alias	=	$join->alias;
					} else {
						$table->category_title	=	'';
						$table->category_alias	=	'';
					}
				}
				if ( JCck::on( '3.1' ) ) {
					$table->tags	=	new JHelperTags;
					$table->tags->getTagIds( $table->id, 'com_content.article' );
				}
			}
		}
		
		return $table;
	}
	
	// _initTable
	protected function _initTable( &$table, &$data, &$config, $force = false )
	{
		$user	=	JFactory::getUser();
		
		if ( ! $table->{self::$key} ) {
			$table->access	=	'';
			parent::g_initTable( $table, ( ( isset( $config['params'] ) ) ? $config['params'] : $this->params->toArray() ), $force );
			$table->{self::$author}		=	$table->{self::$author} ? $table->{self::$author} : JCck::getConfig_Param( 'integration_user_default_author', 42 );
			if ( ( $user->get( 'id' ) > 0 && @$user->guest != 1 ) && !isset( $data[self::$author] ) && !$force ) {
				$data[self::$author]	=	$user->get( 'id' );
			}
		}
		$table->{self::$custom}	=	'';
	}
	
	// _completeTable
	protected function _completeTable( &$table, &$data, &$config )
	{
		if ( $table->state == 1 && intval( $table->publish_up ) == 0 ) {
			$table->publish_up	=	JFactory::getDate()->toSql();
		}
		if ( ! $table->{self::$key} ) {
			$table->modified_by	=	0;
			if ( isset( $config['params'] ) ) {
				$ordering	=	( isset( $config['params']['ordering'] ) ) ? $config['params']['ordering'] : 0;
			} else {
				$ordering	=	$this->params->get( 'ordering', 0 );
			}
			if ( $ordering == 1 ) {
				$max				=	JCckDatabase::loadResult( 'SELECT MAX(ordering) FROM #__content WHERE catid = '.(int)$table->catid );
				$table->ordering	=	(int)$max + 1;
			} elseif ( $ordering == -1 ) {
				if ( !isset( $config['tasks']['reorder'] ) ) {
					$config['tasks']['reorder']	=	array();
				}
				$idx							=	(int)$table->catid;
				if ( !isset( $config['tasks']['reorder'][$idx] ) ) {
					$config['tasks']['reorder'][$idx]	=	'catid = '.(int)$table->catid.' AND state >= 0';	
				}
			} elseif ( $ordering > -1 ) {
				$table->reorder( 'catid = '.(int)$table->catid.' AND state >= 0' );
			}
		}
		if ( ! $table->title ) {
			$table->title	=	JFactory::getDate()->format( 'Y-m-d-H-i-s' );
			$table->alias	=	$table->title;
		}
		$table->version++;
		
		// Readmore
		if ( isset( $config['type_fields_intro'] ) ) {
			$intro	=	(int)$config['type_fields_intro'];
		} else {
			$query	=	'SELECT COUNT(a.fieldid) FROM #__cck_core_type_field AS a LEFT JOIN #__cck_core_types AS b ON b.id = a.typeid'
					.	' WHERE b.name="'.(string)$config['type'].'" AND a.client="intro"';
			$intro	=	(int)JCckDatabase::loadResult( $query );
		}
		if ( $intro > 0 ) {
			if ( isset( $config['params'] ) ) {
				$auto_readmore	=	( isset( $config['params']['auto_readmore'] ) ) ? $config['params']['auto_readmore'] : 1;
			} else {
				$auto_readmore	=	$this->params->get( 'auto_readmore', 1 );
			}
			$table->fulltext	=	'';
			if ( $auto_readmore == 1 ) {
				$table->fulltext	=	'::cck::'.$config['id'].'::/cck::';
			} elseif ( $auto_readmore == 2 ) {	// Legacy
				if ( strlen( strstr( $data['introtext'], '::fulltext::' ) ) > 0 ) {
					$fulltext_exists	=	true;
					if ( strlen( strstr( $data['introtext'], '::fulltext::::/fulltext::' ) ) > 0 ) {
						$fulltext_exists	=	false;
					}
				} else {
					$fulltext_exists	=	false;
				}
				if ( $fulltext_exists ) {
					$table->fulltext	=	'::cck::'.$config['id'].'::/cck::';
				}
			}
		}
		if ( empty( $table->language ) ) {
			$table->language	=	'*';
		}
		parent::g_completeTable( $table, self::$custom, $config );
	}
	
	// _setAssociations
	protected function _setAssociations( $table, $data, $isNew, $config )
	{
		$app	=	JFactory::getApplication();
		$db		=	JFactory::getDbo();

		$associations	=	$data['associations'];
		foreach ( $associations as $tag=>$id ) {
			if ( empty( $id ) ) {
				unset( $associations[$tag] );
			}
		}

		// Detecting all associations
		$all_language	=	$table->language == '*';

		if ( $all_language && !empty( $associations ) ) {
			JError::raiseNotice( 403, JText::_( 'COM_CONTENT_ERROR_ALL_LANGUAGE_ASSOCIATED' ) );
		}
		$associations[$table->language]	=	$table->{self::$key};

		// Deleting old association for these items
		$query	=	$db->getQuery( true )
				->delete( '#__associations' )
				->where( 'context=' . $db->quote( 'com_content.item' ) )
				->where( 'id IN (' . implode(',', $associations ) . ')' );
		$db->setQuery( $query );
		$db->execute();

		if ( $error = $db->getErrorMsg() ) {
			$app->enqueueMessage( $error, 'error' );
			return false;
		}

		if ( !$all_language && count( $associations ) ) {
			// Adding new association for these items
			$key	=	md5( json_encode( $associations ) );
			$query->clear()->insert( '#__associations' );
			foreach ( $associations as $tag=>$id ) {
				$query->values( $id . ',' . $db->quote( 'com_content.item' ) . ',' . $db->quote( $key ) );
			}
			$db->setQuery( $query );
			$db->execute();

			if ( $error = $db->getErrorMsg() ) {
				$app->enqueueMessage( $error, 'error' );
				return false;
			}
		}
	}

	// _setFeatured
	protected function _setFeatured( $table, $isNew )
	{
		require_once JPATH_ADMINISTRATOR.'/components/com_content/tables/featured.php';
		$featured	=	JTable::getInstance( 'Featured', 'ContentTable' );
		
		if ( $isNew ) {
 			if ( $table->featured == 1 ) {
 				JCckDatabase::execute( 'INSERT INTO #__content_frontpage (`content_id`, `ordering`) VALUES ( '.$table->id.', 0)' );
 				$featured->reorder();
 			}
		} else {
			if ( $table->featured == 0 ) {
 				JCckDatabase::execute( 'DELETE FROM #__content_frontpage WHERE content_id = '.(int)$table->id );
 				$featured->reorder();
			} else {
				$id		=	JCckDatabase::loadResult( 'SELECT content_id FROM #__content_frontpage WHERE content_id = '.(int)$table->id );
				if ( ! $id ) {
 					JCckDatabase::execute( 'INSERT INTO #__content_frontpage (`content_id`, `ordering`) VALUES ( '.$table->id.', 0)' );
	 				$featured->reorder();
				}
			}
		}
	}
	
	// -------- -------- -------- -------- -------- -------- -------- -------- // SEF

	// buildRoute
	public static function buildRoute( &$query, &$segments, $config, $menuItem = NULL )
	{
		if ( isset( $query['typeid'] ) ) {
			$segments[]	=	$query['typeid'];
			unset( $query['typeid'] );
		} elseif ( isset( $query['catid'] ) ) {
			if ( is_object( $menuItem ) && $menuItem->alias != $query['catid'] ) {
				$segments[]	=	$query['catid'];
			}
			unset( $query['catid'] );
		}
		
		if ( isset( $query['id'] ) ) {
			if ( self::$sef[$config['doSEF']] == 'full' ) {
				$id		=	$query['id'];
			} else {
				if ( strpos( $query['id'], ':' ) !== false ) {
					if ( self::$sef[$config['doSEF']] == 'alias' ) {
						list( $tmp, $id )	=	explode( ':', $query['id'], 2 );
					} else {
						list( $id, $alias )	=	explode( ':', $query['id'], 2 );
					}
				} else {
					$id		=	$query['id'];
				}
			}
			$segments[]	=	$id;
			unset( $query['id'] );
		}
	}
	
	// getRoute
	public static function getRoute( $item, $sef, $itemId, $config = array(), $lang = '' )
	{
		$route		=	'';
		if ( is_numeric( $item ) ) {
			$item	=	JCckDatabaseCache::loadObject( 'SELECT a.id, a.alias, a.catid, a.language, b.alias AS category_alias'
													 . ' FROM #__content AS a LEFT JOIN #__categories AS b ON b.id = a.catid'
													 . ' WHERE a.id = '.(int)$item );
			if ( empty( $item ) ) {
				return '';
			}
		}
		$pk			=	( isset( $item->pk ) ) ? $item->pk : $item->id;
		$item->slug	=	( $item->alias ) ? $pk.':'.$item->alias : $pk;
		
		if ( $sef ) {
			if ( $sef == '0' || $sef == '1' ) {
				$path	=	'&catid='.$item->catid;
			} elseif ( $sef[0] == '4' ) {
				$path	=	'&catid='.( isset( $item->category_alias ) ? $item->category_alias : $item->catid );
			} elseif ( $sef[0] == '3' ) {
				$path	=	( $config['type'] ) ? '&typeid='.$config['type'] : '';
			} else {
				$path	=	'';
			}
			$route		=	self::_getRoute( $sef, $itemId, $item->slug, $path, '', $lang );
		} else {
			require_once JPATH_SITE.'/components/com_content/helpers/route.php';
			$route		=	ContentHelperRoute::getArticleRoute( $item->slug, $item->catid, $item->language );
		}
		
		return JRoute::_( $route );
	}
	
	// getRouteByStorage
	public static function getRouteByStorage( &$storage, $sef, $itemId, $config = array(), $lang_tag = '' )
	{
		if ( isset( $storage[self::$table]->_route ) && !$lang_tag ) {
			return JRoute::_( $storage[self::$table]->_route );
		}
		
		if ( $sef ) {
			if ( $sef == '0' || $sef == '1' ) {
				$path	=	'&catid='.$storage[self::$table]->catid;
			} elseif ( $sef[0] == '4' ) {
				$path	=	'&catid='.( isset( $storage[self::$table]->category_alias ) ? $storage[self::$table]->category_alias : $storage[self::$table]->catid );
			} elseif ( $sef[0] == '3' ) {
				$path	=	'&typeid='.$config['type'];
			} else {
				$path	=	'';
			}
			if ( is_object( $storage[self::$table] ) ) {
				$storage[self::$table]->_route	=	self::_getRoute( $sef, $itemId, $storage[self::$table]->slug, $path );
			}

			// Multilanguage Associations
			if ( JCckDevHelper::hasLanguageAssociations() ) {
				$app		=	JFactory::getApplication();
				$pk			=	$storage[self::$table]->id;
				if ( $app->input->get( 'view' ) == 'article' && $app->input->get( 'id' ) == $storage[self::$table]->id && !count( self::$routes ) ) {
					JLoader::register( 'MenusHelper', JPATH_ADMINISTRATOR.'/components/com_menus/helpers/menus.php' );
					$assoc_c	=	JLanguageAssociations::getAssociations( 'com_content', '#__content', 'com_content.item', $pk );
					$assoc_m	=	MenusHelper::getAssociations( $itemId );
					$languages	=	JLanguageHelper::getLanguages();
					$lang_code	=	JFactory::getLanguage()->getTag();
					foreach ( $languages as $l ) {
						if ( $lang_code == $l->lang_code ) {
							self::$routes[$l->lang_code]	=	$storage[self::$table]->_route;
						} else {
							$itemId2						=	isset( $assoc_m[$l->lang_code] ) ? $assoc_m[$l->lang_code] : 0;
							$pk2							=	isset( $assoc_c[$l->lang_code] ) ? (int)$assoc_c[$l->lang_code]->id : 0;
							self::$routes[$l->lang_code]	=	'';
							if ( $pk2 && $itemId2 ) {
								self::$routes[$l->lang_code]=	self::getRoute( $pk2, $sef, $itemId2, $config, $l->sef );
							}
						}
					}
				}
			}
		} else {
			require_once JPATH_SITE.'/components/com_content/helpers/route.php';
			$storage[self::$table]->_route	=	ContentHelperRoute::getArticleRoute( $storage[self::$table]->slug, $storage[self::$table]->catid, $storage[self::$table]->language );
		}
		
		return JRoute::_( $storage[self::$table]->_route );
	}

	// parseRoute
	public static function parseRoute( &$vars, $segments, $n, $config )
	{
		$join			=	'';
		$where			=	'';
		
		$vars['option']	=	'com_content';
		$vars['view']	=	'article';
		
		if ( $n == 2 ) {
			if ( $config['doSEF'][0] == '3' ) {
				$join				=	' LEFT JOIN #__cck_core AS b on b.'.$config['join_key'].' = a.id';
				$where				=	' AND b.cck = "'.(string)$segments[0].'"';
			} else {
				$join				=	' LEFT JOIN #__categories AS b on b.id = a.catid';
				if ( $config['doSEF'] == '1'  ) {
					$where			=	' AND b.id = '.(int)$segments[0];
					$vars['catid']	=	$segments[0];
				} else {
					$segments[0]	=	str_replace( ':', '-', $segments[0] );
					$where			=	' AND b.alias = "'.$segments[0].'"';
				}
			}
		} else {
			if ( $config['doSEF'][0] == '2' && isset( $config['doSEF'][1] ) && $config['doSEF'][1] == '4' ) {
				$active				=	JFactory::getApplication()->getMenu()->getActive();
				if ( isset( $active->query['search'] ) && $active->query['search'] ) {
					$cck			=	JCckDatabaseCache::loadResult( 'SELECT sef_route FROM #__cck_core_searchs WHERE name = "'.$active->query['search'].'"' );
					if ( $cck ) {
						$join		=	' LEFT JOIN #__cck_core AS b on b.'.$config['join_key'].' = a.id';
						$where		=	( strpos( $cck, ',' ) !== false ) ? ' AND b.cck IN ("'.str_replace( ',', '","', $cck ).'")' : ' AND b.cck = "'.$cck.'"';
					}
				}
			}
		}
		if ( self::$sef[$config['doSEF']] == 'full' ) {
			$idArray				=	explode( ':', $segments[$n - 1], 2 );
			$vars['id'] 			=	(int)$idArray[0];
		} else {
			if ( is_numeric( $segments[$n - 1] ) ) {
				$vars['id']			=	$segments[$n - 1];
			} else {
				$segments[$n - 1]	=	str_replace( ':', '-', $segments[$n - 1] );
				$query				=	'SELECT a.id FROM '.self::$table.' AS a'
									.	$join
									.	' WHERE a.alias = "'.$segments[$n - 1].'"'.$where;
				$vars['id']			=	(int)JCckDatabaseCache::loadResult( $query );
			}
		}
	}
	
	// setRoutes
	public static function setRoutes( $items, $sef, $itemId )
	{
		if ( count( $items ) ) {
			foreach ( $items as $item ) {
				$item->link	=	self::getRoute( $item, $sef, $itemId );
			}
		}
	}
	
	// _getRoute
	public static function _getRoute( $sef, $itemId, $id, $path = '', $option = '', $lang = '' )
	{
		$option	=	( $option != '' ) ? 'option='.$option.'&' : '';
		$link	=	'index.php?'.$option.'view=article'.$path;

		if ( $id ) {
			$link	.=	'&id='.$id; 
		}
		if ( $itemId ) {
			$link	.=	'&Itemid='.$itemId;
		}
		if ( $lang ) {
			$link	.=	'&lang='.$lang;
		}
		
		return $link;
	}

	// -------- -------- -------- -------- -------- -------- -------- -------- // Stuff
	
	// authorise
	public static function authorise( $rule, $pk )
	{
		return JFactory::getUser()->authorise( $rule, 'com_content.article.'.$pk );
	}

	// checkIn
	public static function checkIn( $pk = 0 )
	{
		if ( !$pk ) {
			return false;
		}
		
		$table	=	self::_getTable( $pk );
		
		return parent::g_checkIn( $table );
	}
	
	// getId
	public static function getId( $config )
	{
		return JCckDatabase::loadResult( 'SELECT id FROM #__cck_core WHERE storage_location="'.self::$type.'" AND pk='.(int)$config['pk'] );
	}
	
	// getStaticProperties
	public static function getStaticProperties( $properties )
	{
		static $autorized	=	array(
									'access'=>'',
									'author'=>'',
									'created_at'=>'',
									'context'=>'',
									'contexts'=>'',
									'custom'=>'',
									'key'=>'',
									'modified_at'=>'',
									'ordering'=>'',
									'parent'=>'',
									'parent_object'=>'',
									'routes'=>'',
									'status'=>'',
									'table'=>'',
									'table_object'=>'',
									'to_route'=>''
								);
		
		if ( count( $properties ) ) {
			foreach ( $properties as $i=>$p ) {
				if ( isset( $autorized[$p] ) ) {
					$properties[$p]	=	self::${$p};
				}
				unset( $properties[$i] );
			}
		}
		
		return $properties;
	}
}
?>