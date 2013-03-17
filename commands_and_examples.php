<?php

/***********************************
 * Commands and examples ***********
 **********************************/

/**
 * Commands in /etc/sphinxsearch
 */

//Stop daemon
sudo searchd --stop

//Reindexar
sudo indexer --all --rotate
sudo indexer --config /etc/sphinxsearch/sphinx.conf.dis --all --rotate

//Start daemon
sudo searchd


//Busquedas
sudo search --config /etc/sphinxsearch/sphinx.conf -a "ACE*"


/**
 *  Examples of SRC
 *--------------------------------------------
 * - Las queries deberán tener un valor autoincrement o la primaykey
 * - Los campos de la query son los campos por los que buscará sphinx 
 * - Los atributos son los valores que quremos que nos retorne sphinx
 * - Si queremos que un campo sea por el cual busqué y además sea retornado
 * el atributo tendrá que ser del tipo  "sql_field_string"
 */

source src1 : connect
{
    # sql_query_pre #################################################################
    sql_query_pre = SET NAMES utf8

    # main document fetch query ######################################################
    sql_query = 	SELECT 
    					c.category_id as category_id, 
    					c.name as name, 
    					COUNT(*) AS total_found  
    				FROM 
    					category c INNER JOIN film_category fc USING( category_id )


    # Attributes ( sql_attr_uint | sql_attr_timestamp | sql_attr_bool | sql_attr_str2ordinal | sql_attr_float ) #########################################
    
    sql_attr_uint   = total_found
	sql_field_string   = name
}


public function getCategoriesMenu()
{	

	$sphinx = new SphinxClient();
	$sphinx->setServer( 'localhost', 3312 );

	//ORDER BY name ASC
	$sphinx->SetSortMode( SPH_SORT_EXTENDED , "name ASC" );
	
	//Muestra todas las categorias excepto(true) aquellas con cantidad 61
	$sphinx->SetFilter( 'total_found', array( 61 ), true );

	//offset, limit, max-maches
	$sphinx->setLimits(100,100,1000);

	//"dog" muestra todos los resultados que matcheen con "dog"
	$results = $sphinx->Query( "", "index_src1" );

	foreach ($results['matches'] as $key => $category) 
	{
		$menu_categories[$key]['name'] = $category['attrs']['name'];
		$menu_categories[$key]['total_found'] = $category['attrs']['total_found'];
	}
	
	return $menu_categories;

}


//Change the weights to give to title column more relevance than 
//description column

$sphinx = new SphinxClient();
$sphinx->setServer( 'localhost', 3312 );
$sphinx->SetMatchMode ( SPH_MATCH_EXTENDED );
$sphinx->SetSortMode( SPH_SORT_RELEVANCE );
$sphinx->SetRankingMode( SPH_RANK_PROXIMITY_BM25 );

$sphinx->SetFieldWeights( array( 'title' => 5 ) );
$res = $sphinx->Query ( "dog", "index_test" );
