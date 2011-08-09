<?php

/**
 * This special page for Semantic MediaWiki implements a customisable form for
 * executing queries outside of articles.
 *
 * @file SMW_SpecialQueryCreator.php
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Sergey Chernyshev
 * @author Devayon Das
 */
class SMWQueryCreatorPage extends SMWQueryUI {

	protected $m_params = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( 'QueryCreator' );
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
	}

	/**
	 * The main entrypoint. Call the various methods of SMWQueryUI and
	 * SMWQueryUIHelper to build ui elements and to process them.
	 *
	 * @global OutputPage $wgOut
	 * @param string $p
	 */
	protected function makePage( $p ) {
		global $wgOut;
		$htmloutput = $this->makeResults( $p );
		if ( $this->uiCore->getQueryString() != "" ) {
			if ( $this->usesNavigationBar() ) {
				$htmloutput .= $this->getNavigationBar ( $this->uiCore->getLimit(), $this->uiCore->getOffset(), $this->uiCore->hasFurtherResults() ); // ? can we preload offset and limit?
			}

			$htmloutput .= "<br/>" . $this->uiCore->getHTMLResult() . "<br>";

			if ( $this->usesNavigationBar() ) {
				$htmloutput .= $this->getNavigationBar ( $this->uiCore->getLimit(), $this->uiCore->getOffset(), $this->uiCore->hasFurtherResults() ); // ? can we preload offset and limit?
			}
		}
		$wgOut->addHTML( $htmloutput );
	}

	/**
	 * Displays a form section showing the options for a given format,
	 * based on the getParameters() value for that format's query printer.
	 *
	 * @param string $format
	 * @param array $paramValues The current values for the parameters (name => value)
	 * @param array $ignoredattribs Attributes which should not be generated by this method.
	 *
	 * @return string
	 *
	 * Overridden from parent to ignore GUI parameters 'format' 'limit' and 'offset'
	 */
	protected function showFormatOptions( $format, array $paramValues, $ignoredattribs = array() ) {
		return parent::showFormatOptions( $format, $paramValues, array( 'format', 'limit', 'offset' ) );
	}
	/**
	 * Creates the input form
	 *
	 * @global OutputPage $wgOut
	 * @return string
	 */
	protected function makeResults() {
		global $wgOut;
		$result = "";
		$spectitle = $this->getTitle();
		$formatBox = $this->getFormatSelectBoxSep( 'broadtable' );
		$result .= '<form name="ask" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
			'<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>';
		$result .= '<br>';
		$result .= wfMsg( 'smw_qc_query_help' );
		// Main query and format options
		$result .= '<table style="width: 100%; ">' .
					'<tr><th>' . wfMsg( 'smw_ask_queryhead' ) . "</th>\n<th>" . wfMsg( 'smw_ask_format_as' ) . "</th></tr>" .
					'<tr>' .
						'<td style="width: 70%; padding-right: 7px;">' . $this->getQueryFormBox() . "</td>\n" .
						'<td style="padding-right: 7px; text-align:center;">' . $formatBox[0] . '</td>' .
					'</tr>' .
					"</table>\n";
		// sorting and prinouts
		$result .= '<div class="smw-qc-sortbox" style="padding-left:10px;">' . $this->getPoSortFormBox() . '</div>';
		// show|hide additional options and querying help
		$result .= '<br><span id="show_additional_options" style="display:inline;"><a href="#addtional" rel="nofollow" onclick="' .
			 "document.getElementById('additional_options').style.display='block';" .
			 "document.getElementById('show_additional_options').style.display='none';" .
			 "document.getElementById('hide_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_qc_show_addnal_opts' ) . '</a></span>';
		$result .= '<span id="hide_additional_options" style="display:none"><a href="#" rel="nofollow" onclick="' .
			 "document.getElementById('additional_options').style.display='none';" .
			 "document.getElementById('hide_additional_options').style.display='none';" .
			 "document.getElementById('show_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_qc_hide_addnal_opts' ) . '</a></span>';
		$result .= ' | <a href="' . htmlspecialchars( wfMsg( 'smw_ask_doculink' ) ) . '">' . wfMsg( 'smw_ask_help' ) . '</a>';
		// additional options
		$result .= '<div id="additional_options" style="display:none">';

		$result .= $formatBox[1]; // display the format options

		if ( $this->uiCore->getQueryString() != '' ) // hide #ask if there isnt any query defined
			$result .= $this->getAskEmbedBox();

		$result .= '</div>'; // end of hidden additional options
		$result .= '<br /><input type="submit" value="' . wfMsg( 'smw_ask_submit' ) . '"/>' .
			'<input type="hidden" name="eq" value="no"/>' .
			"\n</form>";

	return $result;

	}

	/**
	 * Decodes printouts and sorting - related form options generated by its
	 * complement, getPoSortFormBox(). UIs may overload both to change form
	 * parameters.
	 *
	 * Overrides method from SMWQueryUI (modal window added)
	 *
	 * @global boolean $smwgQSortingSupport
	 * @param WebRequest $wgRequest
	 * @return string
	 */
	protected function processPoSortFormBox( WebRequest $wgRequest ) {
		global $smwgQSortingSupport;
		if ( !$smwgQSortingSupport ) return array();

		$params = array();
		$order_values = $wgRequest->getArray( 'order' );
		$property_values = $wgRequest->getArray( 'property' );
		$category_values = $wgRequest->getArray( 'category' );
		$category_label_values = $wgRequest->getArray( 'cat_label' );
		$po = array();
		if ( is_array( $category_values ) ) {
			foreach ( $category_values as $key => $value ) {
				if ( trim( $value ) == '' ) {
					$po[$key] = '?Category'; // Todo: i18n
				} else {
					$po[$key] = '?Category:' . $value; // Todo: i18n
				}
			}
		}
		if ( is_array( $category_label_values ) ) {
			foreach ( $category_label_values as $key => $value ) {
				if ( trim( $value ) != '' ) {
				 $po[$key] .= ' = ' . $value;
				}
			}
		}
		if ( is_array( $property_values ) ) {
			$params['sort'] = '';
			$params['order'] = '';
			foreach ( $property_values as $key => $property_value ) {
				$property_values[$key] = trim( $property_value );
				if ( $property_value == '' ) {
					unset( $property_values[$key] );
				}
				if ( is_array( $order_values ) and array_key_exists( $key, $order_values ) and $order_values[$key] != 'NONE' ) {
					$prop = substr( $property_values[$key], 1 ); // removing the leading '?'
					if ( !strpos( $prop, '#' ) === false ) $prop = strstr( $prop, '#', true ); // removing format options
					if ( !strpos( $prop, '=' ) === false ) $prop = strstr( $prop, '=', true ); // removing format options

					$params['sort'] .= ( $params['sort'] != '' ? ',':'' ) . $prop;
					$params['order'] .= ( $params['order'] != '' ? ',':'' ) . $order_values[$key];
				}
			}
			if ( $params['sort'] == '' ) {
				unset ( $params['sort'] );
			}
			if ( $params['order'] == '' ) {
				unset ( $params['order'] );
			}
			$display_values = $wgRequest->getArray( 'display' );
			if ( is_array( $display_values ) ) {
				foreach ( $display_values as $key => $value ) {
					if ( $value == '1' and array_key_exists( $key, $property_values ) ) {
					$property_values[$key] = trim( $property_values[$key] );
					$property_values[$key] = ( $property_values[$key][0] == '?' ) ? $property_values[$key]:'?' . $property_values[$key];
						$po[$key] = $property_values[$key];
					}
				}
			}
		}
		ksort( $po );
		$params = array_merge( $params, $po );
		return $params;

	}

	/**
	 * Generates the forms elements(s) for choosing printouts and sorting
	 * options. Use its complement processPoSortFormBox() to decode data
	 * sent by these elements.
	 *
	 * Overrides method from SMWQueryUI (modal window added)
	 *
	 * @return string
	 */
	protected function getPoSortFormBox( $enableAutocomplete = SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
		global $smwgQSortingSupport, $wgRequest, $wgOut, $smwgScriptPath;

		if ( !$smwgQSortingSupport ) return '';
		$this->enableJQueryUI();
		$wgOut->addScriptFile( "$smwgScriptPath/libs/jquery-ui/jquery-ui.dialog.min.js" );
		$wgOut->addStyle( "$smwgScriptPath/skins/SMW_custom.css" );

		$result = '';
		$num_sort_values = 0;
		// START: create form elements already submitted earlier via form
		// attempting to load parameters from $wgRequest
		$property_values = $wgRequest->getArray( 'property' );
		$order_values = $wgRequest->getArray( 'order' );
		$display_values = $wgRequest->getArray( 'display' );
		$category_values = $wgRequest->getArray( 'category' );
		$category_label_values = $wgRequest->getArray( 'cat_label' );

		if ( is_array( $property_values ) ) {
			// removing empty values
			foreach ( $property_values as $key => $property_value ) {
				$property_values[$key] = trim( $property_value );
				if ( $property_value == '' ) {
					unset( $property_values[$key] );
				}
			}
		} else {
			/*
			 * Printouts and sorting were set via another widget/form/source, so
			 * create elements by fetching data from $uiCore. The exact ordering
			 * of Ui elements might not be preserved, if the above block were to
			 * be removed. This  is a bit of a hack, converting all strings to
			 * lowercase to simplify searching procedure and using in_array.
			 */

			$po = explode( '?', $this->getPOStrings() );
			reset( $po );
			foreach ( $po as $key => $value ) {
			 $po[$key] = strtolower( trim( $value ) );
			  if ( $po[$key] == '' ) {
				  unset ( $po[$key] );
			  }
			}

			$params = $this->uiCore->getParameters();
			if ( array_key_exists( 'sort', $params ) && array_key_exists( 'order', $params ) ) {
				$property_values = explode( ',', strtolower( $params['sort'] ) );
				$order_values = explode( ',', $params['order'] );
				reset( $property_values );
				reset( $order_values );
			} else {
				$order_values = array(); // do not even show one sort input here
				$property_values = array();
			}

			 foreach ( $po as $po_key => $po_value ) {
				 if ( !in_array( $po_value, $property_values ) ) {
					 $property_values[] = $po_value;
				 }
			 }
			 $display_values = array();
			 reset( $property_values );
			 foreach ( $property_values as $property_key => $property_value ) {
				 if ( in_array( $property_value, $po ) ) {
					 $display_values[$property_key] = "yes";
				 }
			 }
		}
		$i = 0;
		$additional_POs = array();
		if ( is_array( $property_values ) ) {
			$additional_POs = array_merge( $additional_POs, $property_values );
		}
		if ( is_array( $category_values ) ) {// same as testing $category_label_values
			$additional_POs = array_merge( $additional_POs, $category_values );
		}
		ksort( $additional_POs );
		foreach ( $additional_POs as $key => $value ) {
			if ( array_key_exists( $key, $property_values ) ) {
				// make a element for additional properties
				$result .= Html::openElement( 'div', array( 'id' => "sort_div_$i", 'class' => 'smw-sort' ) );
				$result .= '<span class="smw-remove"><a href="javascript:removePOInstance(\'sort_div_' . $i . '\')"><img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMsg( 'smw_qui_delete' ) . '"></a></span>';
				$result .= wfMsg( 'smw_qui_property' );
				$result .= Html::input( 'property[' . $i . ']', $property_values[$key], 'text', array( 'size' => '35', 'id' => "property$i" ) ) . "\n";
				$result .= Html::openElement( 'select', array( 'name' => "order[$i]" ) );

				$if1 = ( !is_array( $order_values ) or !array_key_exists( $key, $order_values ) or $order_values[$key] == 'NONE' );
				$result .= Xml::option( wfMsg( 'smw_qui_nosort' ), "NONE", $if1 );

				$if2 = ( is_array( $order_values ) and array_key_exists( $key, $order_values ) and $order_values[$key] == 'ASC' );
				$result .= Xml::option( wfMsg( 'smw_qui_ascorder' ), "ASC", $if2 );

				$if3 = ( is_array( $order_values ) and array_key_exists( $key, $order_values ) and $order_values[$key] == 'DESC' );
				$result .= Xml::option( wfMsg( 'smw_qui_descorder' ), "DESC", $if3 );

				$result .= Xml::closeElement( 'select' );

				$if4 = ( is_array( $display_values ) and array_key_exists( $key, $display_values ) );
				$result .= Xml::checkLabel( wfMsg( 'smw_qui_shownresults' ), "display[$i]", "display$i", $if4 );
				$result .= ' <a  id="more' . $i . '" "class="smwq-more" href="javascript:smw_makePropDialog(\'' . $i . '\')"> options </a> '; // TODO: i18n

				$result .= Xml::closeElement( 'div' );
				$i++;
			}
			if ( is_array( $category_values ) and array_key_exists( $key, $category_values ) ) {
				$result .= Html::openElement( 'div', array( 'id' => "sort_div_$i", 'class' => 'smw-sort' ) );
				$result .= '<span class="smw-remove"><a href="javascript:removePOInstance(\'sort_div_' . $i . '\')"><img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMsg( 'smw_qui_delete' ) . '"></a></span>' .
							'Category (optional)' . // todo: i18n
							Xml::input( "category[$i]", '20', $category_values[$key] ) . " " .
							'Label' . // todo: i18n
							Xml::input( "cat_label[$i]", '20', array_key_exists( $key, $category_label_values ) ? $category_label_values[$key]:false ) . " " .
							' <a  id="more' . $i . '" "class="smwq-more" href="javascript:smw_makeCatDialog(\'' . $i . '\')"> options </a> ' . // TODO: i18n
							Xml::closeElement( 'div' );
				$i++;
			}
		}
		$num_sort_values = $i;
		// END: create form elements already submitted earlier via form

		// create hidden form elements to be cloned later
		$hiddenproperty = Html::openElement( 'div', array( 'id' => 'property_starter', 'class' => 'smw-sort', 'style' => 'display:none' ) ) .
					'<span class="smw-remove"><a><img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMsg( 'smw_qui_delete' ) . '"></a></span>' .
					wfMsg( 'smw_qui_property' ) .
					Xml::input( "property_num", '35' ) . " " .
					Html::openElement( 'select', array( 'name' => 'order_num' ) ) .
						Xml::option( wfMsg( 'smw_qui_nosort' ), 'NONE' ) .
						Xml::option( wfMsg( 'smw_qui_ascorder' ), 'ASC' ) .
						Xml::option( wfMsg( 'smw_qui_descorder' ), 'DESC' ) .
					Xml::closeElement( 'select' ) .
					Xml::checkLabel( wfMsg( 'smw_qui_shownresults' ), "display_num", '', true ) .
					Xml::closeElement( 'div' );
		$hiddenproperty = json_encode( $hiddenproperty );

		$hiddencategory = Html::openElement( 'div', array( 'id' => 'category_starter', 'class' => 'smw-sort', 'style' => 'display:none' ) ) .
					'<span class="smw-remove"><a><img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMsg( 'smw_qui_delete' ) . '"></a></span>' .
					'Category (optional)' . // todo: i18n
					Xml::input( "category_num", '20' ) . " " .
					'Label' . // todo: i18n
					Xml::input( "cat_label_num", '20' ) . " " .
					Xml::closeElement( 'div' );
		$hiddencategory = json_encode( $hiddencategory );

		$propertydialogbox = Xml::openElement( 'div', array( 'id' => 'prop-dialog', 'title' => 'Property Options', 'class' => 'smw-prop-dialog' ) ) . // todo i18n
					Xml::inputLabel( 'Property:', '', 'd-property', 'd-property' ) . '<br/>' . // todo i18n
					Xml::inputLabel( 'Label:', '', 'd-property-label', 'd-property-label' ) . '<br/>' . // todo i18n
					'Format: ' . Html::openElement( 'select', array( 'name' => 'd-format', 'id' => 'd-format' ) ) . // todo i18n
						Xml::option( 'None (default)', ' ' ) . // todo i18n
						Xml::option( 'Simple', '#-' ) . // todo i18n
					Xml::closeElement( 'select' ) .
					Xml::input( 'format-custom', false, false, array( 'id' => 'd-property-format-custom' ) ) . '<br/>' .
					// Xml::inputLabel( 'Limit:', 'd-property-limit', 'd-property-limit' ) . '<br/>' . // todo i18n
					'<input type="hidden" name="d-property-code" id="d-property-code">' .
					Xml::closeElement( 'div' );
		$categorydialogbox = Xml::openElement( 'div', array( 'id' => 'cat-dialog', 'title' => 'Category Options', 'class' => 'smw-cat-dialog' ) ) . // todo i18n
					Xml::inputLabel( 'Category:', '', 'd-category', 'd-category' ) . '<br/>' . // todo i18n
					Xml::inputLabel( 'Label:', '', 'd-category-label', 'd-category-label' ) . '<br/>' . // todo i18n
					Xml::inputLabel( 'Yes:', '', 'd-category-yes', 'd-category-yes' ) . '<br/>' . // todo i18n
					Xml::inputLabel( 'No:', '', 'd-category-no', 'd-category-no' ) . '<br/>' . // todo i18n
					'Code :<input type="text" name="d-category-code" id="d-category-code">' . // todo hide
					Xml::closeElement( 'div' );

		$result .= '<div id="sorting_main"></div>' . "\n";
		$result .= '[<a href="javascript:smw_addPropertyInstance(\'property_starter\', \'sorting_main\')">' . wfMsg( 'smw_qui_addnprop' ) . '</a>]' .
					'[<a href="javascript:smw_addCategoryInstance(\'category_starter\', \'sorting_main\')">' . 'Add additional category' . '</a>]' . // todo i18n
					"\n";

		// Javascript code for handling adding and removing the "sort" inputs
		if ( $enableAutocomplete == SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
			$this->addAutocompletionJavascriptAndCSS();
		}
		$javascript_text = <<<EOT
<script type="text/javascript">
var num_elements = {$num_sort_values};
EOT;
// add autocomplete
		if ( $enableAutocomplete == SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
			$javascript_text .= <<<EOT

function smw_property_autocomplete(){
	jQuery('[name*="property"]').autocomplete({
		minLength: 2,
		source: function(request, response) {
			url=wgScriptPath+'/api.php?action=opensearch&limit=10&namespace='+wgNamespaceIds['property']+'&format=jsonfm';

			jQuery.getJSON(url, 'search='+request.term, function(data){
				//remove the namespace prefix 'Property:' from returned data
				for(i=0;i<data[1].length;i++) data[1][i]='?'+data[1][i].substr(data[1][i].indexOf(':')+1);
				response(data[1]);
			});

		}
	});
}
function smw_category_autocomplete(){
		jQuery('[name*="category"]').autocomplete({
		minLength: 2,
		source: function(request, response) {
			url=wgScriptPath+'/api.php?action=opensearch&limit=10&namespace='+wgNamespaceIds['category']+'&format=jsonfm';

			jQuery.getJSON(url, 'search='+request.term, function(data){
				//remove the namespace prefix 'Property:' from returned data
				for(i=0;i<data[1].length;i++) data[1][i]=data[1][i].substr(data[1][i].indexOf(':')+1);
				response(data[1]);
			});

		}
	});
}
EOT;
		} else {
			$javascript_text .= <<<EOT
function smw_property_autocomplete(){
}
function smw_category_autocomplete(){
}
EOT;
		}

		$javascript_text .= <<<EOT
function smw_prop_code_update(){
		code = '?'+\$j('#d-property')[0].value;
		if(code!=''){
			if(\$j('#d-property-format-custom')[0].value !=''){
				code = code + \$j('#d-property-format-custom')[0].value;
			}
			if(\$j('#d-property-label')[0].value !=''){
				code = code + ' = '+ \$j('#d-property-label')[0].value;
			}
			\$j('#d-property-code')[0].value= code;
		}
}
function smw_makePropDialog(prop_id){
		jQuery('#prop-dialog input').attr('value','');
		prop=val=\$j('#property'+prop_id)[0].value;
		if(val[0]='?') val=prop=prop.substr(1);
		if((i=val.indexOf('='))!=-1) prop=prop.substring(0, i);
		if((i=val.indexOf('#'))!=-1) prop=prop.substring(0, i);
		if(val.split('=')[1]){
			label=val.split('=')[1].trim();
		}else{
			label="";
		}
		format = val.split('=')[0];
		if(format.indexOf('#')!=-1){
			format=format.substr(format.indexOf('#'));
		}else{
			format="";
		}

		\$j('#d-property').attr('value', prop.trim());
		\$j('#d-property-label').attr('value', label);
		\$j('#d-property-format-custom').attr('value', format.trim());
		\$j('#prop-dialog').dialog.id=prop_id;
		\$j('#prop-dialog').dialog('open');
}
// code for handling adding and removing the "sort" inputs

function smw_addPropertyInstance(starter_div_id, main_div_id) {
	var starter_div = document.getElementById(starter_div_id);
	var main_div = document.getElementById(main_div_id);

	//Create the new instance
	var new_div = starter_div.cloneNode(true);
	var div_id = 'sort_div_' + num_elements;
	new_div.id = div_id;
	new_div.style.display = 'block';
	jQuery(new_div.getElementsByTagName('label')).attr('for', 'display'+num_elements);
	var children = new_div.getElementsByTagName('*');
	var x;
	for (x = 0; x < children.length; x++) {
		if (children[x].for) children[x].for="display"+num_elements;
		if (children[x].name){
			children[x].id = children[x].name.replace(/_num/, ''+num_elements);
			children[x].name = children[x].name.replace(/_num/, '[' + num_elements + ']');
		}
	}

	//Create 'more' link
	var more_button =document.createElement('span');
	more_button.innerHTML = ' <a class="smwq-more" href="javascript:smw_makePropDialog(\'' + num_elements + '\')">options</a> '; //TODO: i18n
	more_button.id = 'more'+num_elements;
	new_div.appendChild(more_button);

	//Add the new instance
	main_div.appendChild(new_div);

	// initialize delete button
	st='sort_div_'+num_elements;
	jQuery('#'+new_div.id).find(".smw-remove a")[0].href="javascript:removePOInstance('"+st+"')";
	num_elements++;
	smw_property_autocomplete();
}

function smw_addCategoryInstance(starter_div_id, main_div_id) {
	var starter_div = document.getElementById(starter_div_id);
	var main_div = document.getElementById(main_div_id);

	//Create the new instance
	var new_div = starter_div.cloneNode(true);
	var div_id = 'sort_div_' + num_elements;
	new_div.id = div_id;
	new_div.style.display = 'block';
	jQuery(new_div.getElementsByTagName('label')).attr('for', 'display'+num_elements);
	var children = new_div.getElementsByTagName('*');
	var x;
	for (x = 0; x < children.length; x++) {
		if (children[x].for) children[x].for="display"+num_elements;
		if (children[x].name){
			children[x].id = children[x].name.replace(/_num/, ''+num_elements);
			children[x].name = children[x].name.replace(/_num/, '[' + num_elements + ']');
		}
	}

	//Create 'more' link
	var more_button =document.createElement('span');
	more_button.innerHTML = ' <a class="smwq-more" href="javascript:smw_makeCatDialog(\'' + num_elements + '\')">options</a> '; //TODO: i18n
	more_button.id = 'more'+num_elements;
	new_div.appendChild(more_button);

	//Add the new instance
	main_div.appendChild(new_div);

	// initialize delete button
	st='sort_div_'+num_elements;
	jQuery('#'+new_div.id).find(".smw-remove a")[0].href="javascript:removePOInstance('"+st+"')";
	num_elements++;
	smw_category_autocomplete();
}

function removePOInstance(div_id) {
	var olddiv = document.getElementById(div_id);
	var parent = olddiv.parentNode;
	parent.removeChild(olddiv);
}

jQuery(function(){
	jQuery('$hiddenproperty').appendTo(document.body);
	jQuery('$hiddencategory').appendTo(document.body);
	jQuery('$propertydialogbox').appendTo(document.body);
	jQuery('$categorydialogbox').appendTo(document.body);
	jQuery('#cat-dialog').dialog({
		autoOpen: false,
		modal: true,
		resizable: true,
		minHeight: 200,
		minWidth: 400,
	});
	jQuery('#prop-dialog').dialog({
		autoOpen: false,
		modal: true,
		resizable: true,
		minHeight: 200,
		minWidth: 400,
		buttons: {
			"Ok": function(){  //todo: i18n
				smw_prop_code_update();
				\$j('#property'+\$j('#prop-dialog').dialog.id)[0].value=\$j('#d-property-code')[0].value;
				jQuery(this).dialog("close");
			},
			"Cancel": function(){ //todo: i18n
				jQuery('#prop-dialog input').attr('value','');
				jQuery(this).dialog("close");
			}
		}
	});
	jQuery('#sort-more').click(function(){jQuery('#prop-dialog').dialog("open");});
	jQuery('#prop-dialog input').bind('keyup click', smw_prop_code_update );

	jQuery('#d-format').bind('change', function(event){
		jQuery('#d-property-format-custom').attr('value', jQuery('#d-format').attr('value'));
		smw_prop_code_update();
	});
});
function smw_makeCatDialog(cat_id){
		//\$j('#cat-dialog').dialog('open');
}
jQuery(document).ready(smw_property_autocomplete);
</script>

EOT;

		$wgOut->addScript( $javascript_text );
		return $result;
	}


	/**
	 * Compatibility method to get the skin; MW 1.18 introduces a getSkin method
	 * in SpecialPage.
	 *
	 * @since 1.6
	 *
	 * @return Skin
	 */
	public function getSkin() {
		if ( method_exists( 'SpecialPage', 'getSkin' ) ) {
			return parent::getSkin();
		} else {
			global $wgUser;
			return $wgUser->getSkin();
		}
	}

}

