<?php
	/* ***********************************************************************
	*	Written 7 June 2012 by Mason Fabel
	*  Revised 8 June 2012 by David Lim
	*  Revised 19 June 2014 by David Lim for Google Spreadsheets V3 API
	*
	*  V2 Description
	*	This function takes a url in the form:
	*	http://spreadsheets.google.com/feeds/cells/$KEY/1/public/values
	*	where $KEY is the key given to the published version of the
	*	spreadsheet.
	*
	*	To publish a spreadsheet in Google Drive (2012), open the
	*	spreadsheet. Under 'file', select 'Publish to the web...'
	*	The key will be a part of the GET portion of the URL listed
	* at the bottom of the dialog box (https://....?key=$KEY&...)
	*
	*	This function returns a multidimensional array in the form:
	*	$array[$row][$col] = $content
	*	where $row is a number and $col is a letter.
	*
	* Limitations
	* This only works for one sheet
	************************************************************************ */

	/* Get a google spreadsheet and return its contents as an array */
	function google_spreadsheet_to_array($key) {
		// initialize URL
			$url = 'http://spreadsheets.google.com/feeds/cells/' . $key . '/1/public/values';

		// initialize curl
			$curl = curl_init();

		// set curl options
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

		// get the spreadsheet using curl
			$google_sheet = curl_exec($curl);

		// close the curl connection
			curl_close($curl);

		// import the xml file into a SimpleXML object
			$feed = new SimpleXMLElement($google_sheet);

		// get every entry (cell) from the xml object
			// extract the column and row from the cell's title
			// e.g. A1 becomes [1][A]

			$array = array();
			foreach ($feed->entry as $entry) {
				$location = (string) $entry->title;
				preg_match('/(?P<column>[A-Z]+)(?P<row>[0-9]+)/', $location, $matches);
		   	$array[$matches['row']][$matches['column']] = (string) $entry->content;
			}

		// return the array
		return $array;
	}

	/*
		Get a google spreadsheet and return its contents as an array
		For version 3.0 of the Google Spreadsheet API, this requires the spreadsheet worksheet
		to be published as a web page. This function will parse through the generated HTML table
		to extract spreadsheet contents.

		This is because API v3 requires authentication and we don't want to put credentials in code.
	*/
	function google_spreadsheet_to_array_v3($url=NULL) {
		// make sure we have a URL
			if (is_null($url)) {
				return array();
			}

		// initialize curl
			$curl = curl_init();

		// set curl options
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

		// get the spreadsheet data using curl
			$google_sheet = curl_exec($curl);

		// close the curl connection
			curl_close($curl);

		// parse out just the html table
			preg_match('/(<table[^>]+>)(.+)(<\/table>)/', $google_sheet, $matches);
			$data = $matches['0'];

		// Convert the HTML into array (by converting into HTML, then JSON, then PHP array
			$cells_xml = new SimpleXMLElement($data);
			$cells_json = json_encode($cells_xml);
			$cells = json_decode($cells_json, TRUE);

		// Create the array
			$array = array();
			foreach ($cells['tbody']['tr'] as $row_number=>$row_data) {
				$column_name = 'A';
				foreach ($row_data['td'] as $column_index=>$column) {
					$array[($row_number+1)][$column_name++] = $column;
				}
			}

		return $array;
	}