<?php
/**
 * Original built by Andreas Piening <andreas (at) silverstripe (dot) com>
 * Modified for SilverStripe 3.1 Linkable Objects module by Sam Costigan <sam (at) stripetheweb (dot) com>
 **/

class DataObjectPicker extends TextField {

	private static $allowed_actions = array(
		'Suggest',
		'Get'
	);

	/**
	 *	$config holds all the Configuration values for the DataObjectPicker
	 **/
	protected $config = array(
		'completeFunction' => array('DataObjectPicker', 'getSuggestions'),
		'searchPattern' => "\"%s\" LIKE '%s%%'",
		'orderBy' => '',
		'limit' => 20,
		'join' => '',
		'readonly' => false,
		'fieldsToSearch' => array()
	);

	public function Field($properties = array()) {
		if(!$this->config['readonly']) {
			Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
			Requirements::javascript(THIRDPARTY_DIR . '/jquery-livequery/jquery.livequery.js');
			Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/src/jquery.selector.affectedby.js');
			Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.core.js');
			Requirements::javascript('linkableobjects/javascript/DataObjectPicker.js');
		}
		Requirements::css('linkableobjects/css/DataObjectPicker.css');
		
		$current = $this->Value() ? DataObject::get_by_id($this->classToPick(), $this->Value()) : false;
		if($current) {
			$sf = $this->summaryFields();
			$full = array();
			foreach($sf as $f) if($current->$f) $full[] = $current->$f;
			if(empty($full)) $full[] = 'undefined dataobject';
			$nice = implode(',', $full);
		} else {
			$nice = '-- none selected --';
		}

		$html =
			"<p><em class='DataObjectPickerMessage'>Type to search.</em></p>".
			$this->createTag('input', array(
				'type' => 'hidden',
				'class' => 'DataObjectPicker',
				'id' => $this->id(),
				'name' => $this->getName(),
				'value' => $this->Value(),
			));
		if($this->config['readonly']) {
			$html .=
				$this->createTag('span', array(
					'class' => 'DataObjectPickerHelper text readonly' . ($this->extraClass() ? $this->extraClass() : ''),
					'id' => $this->id() . '_helper',
					'tabindex' => !!($this->getAttribute('tabindex')),
					'readonly' => 'readonly',
				), $nice);
		} else {
			$html .=
				$this->createTag('input', array(
					'type' => 'text',
					'autocomplete' => 'off',
					'class' => 'DataObjectPickerHelper text' . ($this->extraClass() ? $this->extraClass() : ''),
					'id' => $this->id() . '_helper',
					'name' => $this->getName() . '_helper',
					'value' => $nice,
					'tabindex' => !!($this->getAttribute('tabindex')),
					'maxlength' => ($this->maxLength) ? $this->maxLength : null,
					'size' => ($this->maxLength) ? min( $this->maxLength, 30 ) : null,
					'rel' => $this->form ? $this->Link() : 'admin/EditForm/field/' . $this->getName(),
				)).
				$this->createTag('ul', array(
					'class' => 'DataObjectPickerSuggestions',
					'id' => $this->id() . '_suggestions',
				));
		}
		
		return $html . $this->createTag('div', array('style' => 'clear:both;'));
	}
	
	/**
	 *	Set some configuration parameters that differ from what DataObject would expect or unguessable
	 **/
	public function setConfig($key, $val) {
		$this->config[$key] = $val;
	}

	/**
	 *	Return field holder to the form
	 **/
	public function FieldHolder($properties = array()) {
		return parent::FieldHolder($properties);
	}

	/**
	 *	Call lookup callback and pass it the request
	 *	Use DataObjectPicker::setConfig() to specify a callback.
	 *	@param Request Object since this is called by handleAction()
	 *	@return String JSON fomatted ennumerated array containing assosiative array with id, title and full for each suggested DataObject
	 **/
	public function Suggest($request) {
		return $this->getSuggestions($request);
	}

	/**
	 * Get a specific Object based on the ID passed as the request parameter
	 * and return the objects title
	 *
	 * @return JSON String
	 */
	public function Get($request) {
		// Fetch the id value from the 'request' parameter
		$id = $request->requestVar('request');

		// If the id we were provided isn't a number, simply return
		if(!is_numeric($id)) return;

		// Get the DataObject which matches the Class and ID value that we want to get
		$obj = DataObject::get($this->classToPick())->byID($id);

		// Store the objects title in an array
		$json = array(
			'title' => Convert::raw2att($obj->LinkTitle())
		);
		// Finally, return that array as a JSON string
		return json_encode($json);
	}

	private function getSuggestions($req) {
		
		$request = Convert::raw2sql($req->requestVar('request'));

		// If the class to pick config value is not set, this will not work too well!
		// In that case, we'll just return an empty result.
		if(empty($this->config['classToPick'])) {
			$results = array(
				array(
					'id' => '0',
					'title' => 'none selected',
					'full' => "select none",
					'style' => 'color:red'
				)
			);
		} else {
			$class = $this->config['classToPick'];
			$search = Config::inst()->get($class, 'searchable_fields');
			$searchArray = array();

			$sqlQuery = new SQLQuery();
			$sqlQuery->setFrom($class);
			$sqlQuery->selectField('ID');
			$sqlQuery->useDisjunction();

			foreach ($search as $key => $value) {
				if(!is_array($value)) {
					if(is_string($key)) {
						$sqlQuery->addWhere("$key LIKE '%$request%'");
					} else {
						$sqlQuery->addWhere("$value LIKE '%$request%'");
					}
				}
			}

			$results = array(
				array(
					'id' => '0',
					'title' => 'none selected',
					'full' => "select none",
					'style' => 'color:red'
				)
			);

			$dbResults = $sqlQuery->execute();

			foreach ($dbResults as $row) {

				$object = DataObject::get($class)->byID($row['ID']);

				$results[] = array(
					'id' => $object->ID,
					'title' => $object->LinkTitle(),
					'full' => $object->LinkTitle()
				);
			}
		}

		return json_encode($results);
	}

	/**
	 *	Get class to pick objects from, uses DataObjectPicker::config['classToPick'] and if empty
	 *	tries to resolve the class by looking up the records has_one relations. Set $name properly
	 *	for this to work.
	 *	@return String class name that can be picked from
	 **/
	protected function classToPick() {
		if(empty($this->config['classToPick'])) {
			$recordClass = $this->Form->getRecord() ? get_class($this->Form->getRecord()) : false;
			$relationName = substr($this->getName(), -2) == 'ID' ? substr($this->getName(), 0, -2) : false;
			$relationNames = $recordClass ? Config::inst()->get($recordClass, 'has_one') : array();
			if(empty($relationNames[$relationName])) trigger_error("Can't figure out which class to search in. Please setup 'classToPick' using DataObjectPicker::setConfig().");
			$this->config['classToPick'] = $relationNames[$relationName];
		}
		return $this->config['classToPick'];
	}
	
	/**
	 *	Get summaryFields to describe the suggested DataObjects, uses DataObjectPicker::config['summaryFields'] and if empty
	 *	tries to guess by looking into the static $summary_fields of the classToPick(). Set static $summary_fields properly
	 *	for the guessing to work.
	 *	@return Array of summaryFields
	 **/
	protected function summaryFields() {
		if(empty($this->config['summaryFields'])) {
			if(Config::inst()->get($this->classToPick(), 'summary_fields')) {
				$this->config['summaryFields'] = array();
				foreach(Config::inst()->get($this->classToPick(), 'summary_fields') as $key => $val) {
					$sf = is_numeric($key) ? $val : $key;
					if(strpos($sf, '.') === false) $this->config['summaryFields'][] = trim($sf, '"');
				}
			} else {
				$this->config['summaryFields'] = array();
				foreach(Config::inst()->get($this->classToPick(), 'db') as $sf => $void) if(count($this->config['classToPick']) < 3) $this->config['summaryFields'][] = $sf;
			}
		}
		return $this->config['summaryFields'];
	}
	
	/**
	 *	Return a 
	 *	@return DataObjectPicker instance that is readonly
	 **/
	function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setConfig('readonly', true);
		return $clone;
	}
}
