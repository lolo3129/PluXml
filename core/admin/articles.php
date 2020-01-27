<?php

/**
 * Listing des articles
 *
 * @package PLX
 * @author	Stephane F et Florent MONTHEL
 **/

include __DIR__ .'/prepend.php';
use Pluxml\PlxToken;
use Pluxml\PlxUtils;
use Pluxml\PlxDate;
use Pluxml\PlxMsg;

# Control du token du formulaire
PlxToken::validateFormToken($_POST);

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminIndexPrepend'));

# Suppression des articles selectionnes
if(isset($_POST['delete']) AND isset($_POST['idArt'])) {
	foreach ($_POST['idArt'] as $k => $v) $plxAdmin->delArticle($v);
	header('Location: index.php');
	exit;
}

# Récuperation de l'id de l'utilisateur
$userId = ($_SESSION['profil'] < PROFIL_WRITER ? '[0-9]{3}' : $_SESSION['user']);

# Récuperation des paramètres
if(!empty($_GET['sel']) AND in_array($_GET['sel'], array('all','published', 'draft','mod'))) {
	$_SESSION['sel_get']=PlxUtils::nullbyteRemove($_GET['sel']);
	$_SESSION['sel_cat']='';
}
else
	$_SESSION['sel_get']=(isset($_SESSION['sel_get']) AND !empty($_SESSION['sel_get']))?$_SESSION['sel_get']:'all';

if(!empty($_POST['sel_cat']))
	if(isset($_SESSION['sel_cat']) AND $_SESSION['sel_cat']==$_POST['sel_cat']) # annulation du filtre
		$_SESSION['sel_cat']='all';
	else # prise en compte du filtre
		$_SESSION['sel_cat']=$_POST['sel_cat'];
else
	$_SESSION['sel_cat']=(isset($_SESSION['sel_cat']) AND !empty($_SESSION['sel_cat']))?$_SESSION['sel_cat']:'all';

# Recherche du motif de sélection des articles en fonction des paramètres
$catIdSel = '';
$mod='';
switch ($_SESSION['sel_get']) {
case 'published':
	$catIdSel = '[home|0-9,]*FILTER[home|0-9,]*';
	$mod='';
	break;
case 'draft':
	$catIdSel = '[home|0-9,]*draft,FILTER[home|0-9,]*';
	$mod='_?';
	break;
case 'all':
	$catIdSel = '[home|draft|0-9,]*FILTER[draft|home|0-9,]*';
	$mod='_?';
	break;
case 'mod':
	$catIdSel = '[home|draft|0-9,]*FILTER[draft|home|0-9,]*';
	$mod='_';
	break;
}

switch ($_SESSION['sel_cat']) {
case 'all' :
	$catIdSel = str_replace('FILTER', '', $catIdSel); break;
case '000' :
	$catIdSel = str_replace('FILTER', '000', $catIdSel); break;
case 'home':
	$catIdSel = str_replace('FILTER', 'home', $catIdSel); break;
case preg_match('/^[0-9]{3}$/', $_SESSION['sel_cat'])==1:
	$catIdSel = str_replace('FILTER', $_SESSION['sel_cat'], $catIdSel);
}

# Nombre d'article sélectionnés
$nbArtPagination = $plxAdmin->nbArticles($catIdSel, $userId);

# Récupération du texte à rechercher
$artTitle = (!empty($_GET['artTitle']))?PlxUtils::unSlash(trim(urldecode($_GET['artTitle']))):'';
if(empty($artTitle)) {
	 $artTitle = (!empty($_POST['artTitle']))?PlxUtils::unSlash(trim(urldecode($_POST['artTitle']))):'';
}
$_GET['artTitle'] = $artTitle;

# On génère notre motif de recherche
if(is_numeric($_GET['artTitle'])) {
	$artId = str_pad($_GET['artTitle'],4,'0',STR_PAD_LEFT);
	$motif = '/^'.$mod.$artId.'.'.$catIdSel.'.'.$userId.'.[0-9]{12}.(.*).xml$/';
} else {
	$motif = '/^'.$mod.'[0-9]{4}.'.$catIdSel.'.'.$userId.'.[0-9]{12}.(.*)'.PlxUtils::urlify($_GET['artTitle']).'(.*).xml$/';
}
# Calcul du nombre de page si on fait une recherche
if($_GET['artTitle']!='') {
	if($arts = $plxAdmin->plxGlob_arts->query($motif))
		$nbArtPagination = sizeof($arts);
}

# Traitement
$plxAdmin->prechauffage($motif);
$plxAdmin->getPage();
$arts = $plxAdmin->getArticles('all'); # Recuperation des articles

# Génération de notre tableau des catégories
$aFilterCat['all'] = L_ARTICLES_ALL_CATEGORIES;
$aFilterCat['home'] = L_CATEGORY_HOME;
$aFilterCat['000'] = L_UNCLASSIFIED;
if($plxAdmin->aCats) {
	foreach($plxAdmin->aCats as $k=>$v) {
		$aCat[$k] = PlxUtils::strCheck($v['name']);
		$aFilterCat[$k] = PlxUtils::strCheck($v['name']);
	}
	$aAllCat[L_CATEGORIES_TABLE] = $aCat;
}
$aAllCat[L_SPECIFIC_CATEGORIES_TABLE]['home'] = L_CATEGORY_HOME_PAGE;
$aAllCat[L_SPECIFIC_CATEGORIES_TABLE]['draft'] = L_DRAFT;
$aAllCat[L_SPECIFIC_CATEGORIES_TABLE][''] = L_ALL_ARTICLES_CATEGORIES_TABLE;

# On inclut le header
include __DIR__ .'/top.php';
?>

<?php eval($plxAdmin->plxPlugins->callHook('AdminIndexTop')) # Hook Plugins ?>

<div class="adminheader">
	<h2><?= L_ARTICLES_LIST ?></h2>
	<ul>
		<li><a <?= ($_SESSION['sel_get']=='all')?'class="selected" ':'' ?>href="index.php?sel=all&amp;page=1"><?= L_ALL ?></a><?= '&nbsp;('.$plxAdmin->nbArticles('all', $userId).')' ?></li>
		<li><a <?= ($_SESSION['sel_get']=='published')?'class="selected" ':'' ?>href="index.php?sel=published&amp;page=1"><?= L_ALL_PUBLISHED ?></a><?= '&nbsp;('.$plxAdmin->nbArticles('published', $userId, '').')' ?></li>
		<li><a <?= ($_SESSION['sel_get']=='draft')?'class="selected" ':'' ?>href="index.php?sel=draft&amp;page=1"><?= L_ALL_DRAFTS ?></a><?= '&nbsp;('.$plxAdmin->nbArticles('draft', $userId).')' ?></li>
		<li><a <?= ($_SESSION['sel_get']=='mod')?'class="selected" ':'' ?>href="index.php?sel=mod&amp;page=1"><?= L_ALL_AWAITING_MODERATION ?></a><?= '&nbsp;('.$plxAdmin->nbArticles('all', $userId, '_').')' ?></li>
	</ul>
</div>

<div class="admin">
<?php
	if(is_file(PLX_ROOT.'install.php'))
		echo '<p class="alert red">'.L_WARNING_INSTALLATION_FILE.'</p>'."\n";
	PlxMsg::Display();
	# Hook Plugins
	eval($plxAdmin->plxPlugins->callHook('AdminTopBottom'));
?>

<form action="index.php" method="post" id="form_articles">

<div class="autogrid mts mbs">
	<div>
		<?php PlxUtils::printSelect('sel_cat', $aFilterCat, $_SESSION['sel_cat']) ?>
		<input class="<?= $_SESSION['sel_cat']!='all'?' select':'' ?> btn--primary" type="submit" value="<?= L_ARTICLES_FILTER_BUTTON ?>">
	</div>
	<div class="txtright">
		<input id="index-search" placeholder="<?= L_SEARCH_PLACEHOLDER ?>" type="text" name="artTitle" value="<?= PlxUtils::strCheck($_GET['artTitle']) ?>" />
		<input class="<?= (!empty($_GET['artTitle'])?' select':'') ?> btn--primary" type="submit" value="<?= L_SEARCH ?>" />
	</div>
</div>

<div>
	<table class="table">
		<thead>
			<tr>
				<th class="checkbox"><input type="checkbox" onclick="checkAll(this.form, 'idArt[]')" /></th>
				<th><?= L_ID ?></th>
				<th><?= L_ARTICLE_LIST_DATE ?></th>
				<th><?= L_ARTICLE_LIST_TITLE ?></th>
				<th><?= L_ARTICLE_LIST_CATEGORIES ?></th>
				<th><?= L_ARTICLE_LIST_NBCOMS ?></th>
				<th><?= L_ARTICLE_LIST_AUTHOR ?></th>
				<th class="action"><?= L_ARTICLE_LIST_ACTION ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		# On va lister les articles
		if($arts) { # On a des articles
			# Initialisation de l'ordre
			$num=0;
			$datetime = date('YmdHi');
			while($plxAdmin->plxRecord_arts->loop()) { # Pour chaque article
				$author = PlxUtils::getValue($plxAdmin->aUsers[$plxAdmin->plxRecord_arts->f('author')]['name']);
				$publi = (boolean)!($plxAdmin->plxRecord_arts->f('date') > $datetime);
				# Catégories : liste des libellés de toutes les categories
				$draft='';
				$libCats='';
				$aCats = array();
				$catIds = explode(',', $plxAdmin->plxRecord_arts->f('categorie'));
				if(sizeof($catIds)>0) {
					foreach($catIds as $catId) {
						$selected = ($catId==$_SESSION['sel_cat'] ? ' selected="selected"' : '');
						if($catId=='draft') $draft = ' - <strong>'.L_CATEGORY_DRAFT.'</strong>';
						elseif($catId=='home') $aCats['home'] = '<option value="home"'.$selected.'>'.L_CATEGORY_HOME.'</option>';
						elseif($catId=='000') $aCats['000'] = '<option value="000"'.$selected.'>'.L_UNCLASSIFIED.'</option>';
						elseif(isset($plxAdmin->aCats[$catId])) $aCats[$catId] = '<option value="'.$catId.'"'.$selected.'>'.PlxUtils::strCheck($plxAdmin->aCats[$catId]['name']).'</option>';
					}

				}
				# en attente de validation ?
				$idArt = $plxAdmin->plxRecord_arts->f('numero');
				$awaiting = $idArt[0]=='_' ? ' - <strong>'.L_AWAITING.'</strong>' : '';
				# Commentaires
				$nbComsToValidate = $plxAdmin->getNbCommentaires('/^_'.$idArt.'.(.*).xml$/','all');
				$nbComsValidated = $plxAdmin->getNbCommentaires('/^'.$idArt.'.(.*).xml$/','all');
				# On affiche la ligne
				echo '<tr>';
				echo '<td><input type="checkbox" name="idArt[]" value="'.$idArt.'" /></td>';
				echo '<td>'.$idArt.'</td>';
				echo '<td>'.PlxDate::formatDate($plxAdmin->plxRecord_arts->f('date')).'&nbsp;</td>';
				echo '<td class="wrap"><a href="article.php?a='.$idArt.'" title="'.L_ARTICLE_EDIT_TITLE.'">'.PlxUtils::strCheck($plxAdmin->plxRecord_arts->f('title')).'</a>'.$draft.$awaiting.'&nbsp;</td>';
				echo '<td>';
				if(sizeof($aCats)>1) {
					echo '<select name="sel_cat2" class="ddcat" onchange="this.form.sel_cat.value=this.value;this.form.submit()">';
					echo implode('', $aCats);
					echo '</select>';
				}
				else echo strip_tags(implode('', $aCats));
				echo '&nbsp;</td>';
				echo '<td><a title="'.L_NEW_COMMENTS_TITLE.'" href="comments.php?sel=offline&amp;a='.$plxAdmin->plxRecord_arts->f('numero').'&amp;page=1">'.$nbComsToValidate.'</a> / <a title="'.L_VALIDATED_COMMENTS_TITLE.'" href="comments.php?sel=online&amp;a='.$plxAdmin->plxRecord_arts->f('numero').'&amp;page=1">'.$nbComsValidated.'</a>&nbsp;</td>';
				echo '<td>'.PlxUtils::strCheck($author).'&nbsp;</td>';
				echo '<td>';
				echo '<a href="article.php?a='.$idArt.'" title="'.L_ARTICLE_EDIT_TITLE.'">'.L_ARTICLE_EDIT.'</a>';
				if($publi AND $draft=='') # Si l'article est publié
					echo ' <a href="'.$plxAdmin->urlRewrite('?article'.intval($idArt).'/'.$plxAdmin->plxRecord_arts->f('url')).'" title="'.L_ARTICLE_VIEW_TITLE.'">'.L_VIEW.'</a>';
				echo "&nbsp;</td>";
				echo "</tr>";
			}
		} else { # Pas d'article
			echo '<tr><td colspan="8" class="center">'.L_NO_ARTICLE.'</td></tr>';
		}
		?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="8">
					<?php
						echo PlxToken::getTokenPostMethod();
						if($_SESSION['profil']<=PROFIL_MODERATOR) {
							echo '<input class="btn--warning" name="delete" type="submit" value="'.L_DELETE.'" onclick="return confirmAction(this.form, \'id_selection\', \'delete\', \'idArt[]\', \''.L_CONFIRM_DELETE.'\')" /><span class="sml-hide med-show">&nbsp;&nbsp;&nbsp;</span>';
						}
						PlxUtils::printInput('page',1,'hidden'); ?>
				</td>
			</tr>
		</tfoot>
	</table>
</div>

</form>

<p>
	<?php
	# Hook Plugins
	eval($plxAdmin->plxPlugins->callHook('AdminIndexPagination'));
	# Affichage de la pagination
	if($arts) { # Si on a des articles (hors page)
		# Calcul des pages
		$last_page = ceil($nbArtPagination/$plxAdmin->bypage);
		$stop = $plxAdmin->page + 2;
		if($stop<5) $stop=5;
		if($stop>$last_page) $stop=$last_page;
		$start = $stop - 4;
		if($start<1) $start=1;
		# Génération des URLs
		$artTitle = (!empty($_GET['artTitle'])?'&amp;artTitle='.urlencode($_GET['artTitle']):'');
		$p_url = 'index.php?page='.($plxAdmin->page-1).$artTitle;
		$n_url = 'index.php?page='.($plxAdmin->page+1).$artTitle;
		$l_url = 'index.php?page='.$last_page.$artTitle;
		$f_url = 'index.php?page=1'.$artTitle;
		# Affichage des liens de pagination
		printf('<span class="p_page">'.L_PAGINATION.'</span>', '<input style="text-align:right;width:35px" onchange="window.location.href=\'index.php?page=\'+this.value+\''.$artTitle.'\'" value="'.$plxAdmin->page.'" />', $last_page);
		$s = $plxAdmin->page>2 ? '<a href="'.$f_url.'" title="'.L_PAGINATION_FIRST_TITLE.'">&laquo;</a>' : '&laquo;';
		echo '<span class="p_first">'.$s.'</span>';
		$s = $plxAdmin->page>1 ? '<a href="'.$p_url.'" title="'.L_PAGINATION_PREVIOUS_TITLE.'">&lsaquo;</a>' : '&lsaquo;';
		echo '<span class="p_prev">'.$s.'</span>';
		for($i=$start;$i<=$stop;$i++) {
			$s = $i==$plxAdmin->page ? $i : '<a href="'.('index.php?page='.$i.$artTitle).'" title="'.$i.'">'.$i.'</a>';
			echo '<span class="p_current">'.$s.'</span>';
		}
		$s = $plxAdmin->page<$last_page ? '<a href="'.$n_url.'" title="'.L_PAGINATION_NEXT_TITLE.'">&rsaquo;</a>' : '&rsaquo;';
		echo '<span class="p_next">'.$s.'</span>';
		$s = $plxAdmin->page<($last_page-1) ? '<a href="'.$l_url.'" title="'.L_PAGINATION_LAST_TITLE.'">&raquo;</a>' : '&raquo;';
		echo '<span class="p_last">'.$s.'</span>';
	}
	?>
</p>

<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminIndexFoot'));
# On inclut le footer
include __DIR__ .'/foot.php';
?>