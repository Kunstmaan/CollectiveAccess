<?php
/**
 * Logiciel : HTML2PDF
 * 
 * Convertisseur HTML => PDF, utilise TCPDF 
 * Distribu� sous la licence LGPL. 
 *
 * @author		Laurent MINGUET <webmaster@html2pdf.fr>
 * @version		4.01
 */

if (!defined('__CLASS_HTML2PDF__'))
{
	define('__CLASS_HTML2PDF__', '4.01');

	require_once(dirname(__FILE__).'/_mypdf/mypdf.class.php');	// classe mypdf
	require_once(dirname(__FILE__).'/parsingHTML.class.php');	// classe de parsing HTML
	require_once(dirname(__FILE__).'/styleHTML.class.php');		// classe de gestion des styles

	class HTML2PDF
	{
		public	$pdf				= null;		// objet PDF
		public	$style				= null;		// objet de style
		public	$parsing			= null;		// objet de parsing
		
		protected	$langue				= 'fr';		// langue des messages
		protected	$sens				= 'P';		// sens d'affichage Portrait ou Landscape
		protected	$format				= 'A4';		// format de la page : A4, A3, ...
		protected	$encoding			= '';		// charset encoding
		protected	$unicode			= true;		// means that the input text is unicode (default = true)
		
		protected	$background			= array();	// informations sur le background
		protected	$testTDin1page		= true;		// activer le test de TD ne devant pas depasser une page
		protected	$testIsImage		= true;		// test si les images existes ou non
		protected	$testIsDeprecated	= false;	// test si certaines fonctions sont deprecated
		protected	$isSubPart			= false;	// indique que le convertisseur courant est un sous html
		
		protected	$parse_pos			= 0;		// position du parsing
		protected	$temp_pos			= 0;		// position temporaire pour multi tableau
		protected	$page				= 0;		// numero de la page courante
		
		protected	$sub_html			= null;		// sous html
		protected	$sub_part			= false;	// indicateur de sous html
		
		protected	$maxX				= 0;		// zone maxi X
		protected	$maxY				= 0;		// zone maxi Y
		protected	$maxE				= 0;		// nomre d'elements dans la zone
		protected	$maxH				= 0;		// plus grande hauteur dans la ligne, pour saut de ligne � corriger
		protected	$maxSave			= array();	// tableau de sauvegarde des maximaux
		protected	$currentH			= 0;		// hauteur de la ligne courante
		
		protected	$firstPage			= true;		// premier page
		
		protected	$defaultLeft		= 0;		// marges par default de la page
		protected	$defaultTop			= 0;
		protected	$defaultRight		= 0;
		protected	$defaultBottom		= 0;
		
		protected	$margeLeft			= 0;		//marges r�elles de la page
		protected	$margeTop			= 0;
		protected	$margeRight			= 0;
		protected	$margeBottom		= 0;
		protected	$marges				= array();	// tableau de sauvegarde des differents etats des marges de la page courante
		
		protected	$inLink				= '';		// indique si on est � l'interieur d'un lien
		protected	$lstAncre			= array();	// liste des ancres d�tect�es ou cr��es
		protected	$subHEADER			= array();	// tableau des sous commandes pour faire l'HEADER
		protected	$subFOOTER			= array();	// tableau des sous commandes pour faire le FOOTER
		protected	$subSTATES			= array();	// tableau de sauvegarde de certains param�tres
		protected	$defLIST			= array();	// tableau de sauvegarde de l'etat des UL et OL
		
		protected	$lstChamps			= array();	// liste des champs
		protected	$lstSelect			= array();	// options du select en cours
		protected	$previousCall		= null;		// dernier appel
		protected	$pageMarges			= array();	// marges sp�cifiques dues aux floats
		protected	$isInThead			= false;	// indique si on est dans un thead
		protected	$isInTfoot			= false;	// indique si on est dans un tfoot
		protected	$isInOverflow		= false;	// indique si on est dans une div overflow
		protected	$isInFooter			= false;	// indique si on est dans un footer ou non
		protected	$isInDraw			= null;		// indique si on est en mode dessin
		protected	$isAfterFloat		= false;	// indique si on est apres un float
		protected	$forOneLine			= false;	// indique si on est dans un sous HTML ne servant qu'a calculer la taille de la prochaine ligne
		protected	$isInForm			= false;	// indique si on est dans un formulaire. Contient dans ce cas l� l'action de celui-ci
		
		protected	$DEBUG_actif		= false;	// indique si on est en mode debug
		protected	$DEBUG_ok_usage		= false;	// indique l'existance de la fonction memory_get_usage
		protected	$DEBUG_ok_peak		= false;	// indique l'existance de la fonction memory_get_peak_usage
		protected	$DEBUG_level		= 0;		// niveau du debug
		protected	$DEBUG_start_time	= 0;		// 
		protected	$DEBUG_last_time	= 0;		// 
		protected	$defaultFont		= null;		// fonte par d�faut si la fonte demand�e n'existe pas

		static protected $SUBOBJ		= null;		// sous objet HTML2PDF pr�par� en cas de besoin
		static protected $TABLES		= array();	// tableau global necessaire � la gestion des tables imbriqu�es 
		static protected $TEXTES		= array();	// tableau comprennant le fichier de langue
		
		/**
		 * Constructeur
		 *
		 * @param	string		sens portrait ou landscape
		 * @param	string		format A4, A5, ...
		 * @param	string		langue : fr, en, it...
		 * @param	boolean		$unicode TRUE means that the input text is unicode (default = true)
		 * @param 	String		$encoding charset encoding; default is UTF-8
		 * @param	array		marges par defaut, dans l'ordre (left, top, right, bottom)
		 * @return	null
		 */
		public function __construct($sens = 'P', $format = 'A4', $langue='fr', $unicode=true, $encoding='UTF-8', $marges = array(5, 5, 5, 8))
		{
			// sauvegarde des param�tres
			$this->page 		= 0;
			$this->sens			= $sens;
			$this->format		= $format;
			$this->unicode		= $unicode;
			$this->encoding		= $encoding;
			
			$this->firstPage	= true;
			$this->langue		= strtolower($langue);
			
			// chargement du fichier de langue
			HTML2PDF::textLOAD($this->langue);
			
			// cr�ation de l' objet PDF
			$this->pdf = new MyPDF($sens, 'mm', $format, $unicode, $encoding);

			// initialisation des styles
			$this->style = new styleHTML($this->pdf);
			$this->style->FontSet();
			$this->defLIST = array();
			
			// initialisations diverses
			$this->setTestTdInOnePage(true);
			$this->setTestIsImage(true);
			$this->setTestIsDeprecated(true);
			$this->setDefaultFont(null);
			
			// initialisation du parsing
			$this->parsing = new parsingHTML($this->encoding);
			$this->sub_html = null; 
			$this->sub_part	= false;
			
			// initialisation des marges
			if (!is_array($marges)) $marges = array($marges, $marges, $marges, $marges);	
			$this->setDefaultMargins($marges[0], $marges[1], $marges[2], $marges[3]);
			$this->setMargins();
			$this->marges = array();

			// initialisation des champs de formulaire
			$this->lstChamps = array();
		}
		
		/**
		* Destructeur
		*
		* @return	null
		*/
		public function __destruct()
		{
			
		}
		
		/**
		* activer le debug mode
		*
		* @return	null
		*/
		public function setModeDebug()
		{
			list($usec, $sec) = explode(' ', microtime());
			
			$this->DEBUG_actif = true;
			$this->DEBUG_ok_usage = function_exists('memory_get_usage');
			$this->DEBUG_ok_peak = function_exists('memory_get_peak_usage');
			$this->DEBUG_start_time		= (float)$sec + (float)$usec;
			$this->DEBUG_last_time		= (float)$sec + (float)$usec;
			
			$this->DEBUG_stepline('step', 'time', 'delta', 'memory', 'peak');
			$this->DEBUG_add('Init debug');
		}
		
		/**
		* rajouter une ligne de debug
		*
		* @param	string	nom de l'etape
		* @param	boolean true=monter d'un niveau, false=descendre d'un niveau, null : ne rien faire
		* @return	null
		*/
		protected function DEBUG_add($nom, $level=null)
		{
			list($usec, $sec) = explode(' ', microtime());
			if ($level===true) $this->DEBUG_level++;

			$nom	= str_repeat('  ',$this->DEBUG_level). $nom.($level===true ? ' Begin' : ($level===false ? ' End' : ''));
			$time	= (float)$sec + (float)$usec;
			$usage	= ($this->DEBUG_ok_usage ? memory_get_usage() : 0);
			$peak	= ($this->DEBUG_ok_peak ? memory_get_peak_usage() : 0);

			$this->DEBUG_stepline(
						$nom,
						number_format(($time - $this->DEBUG_start_time)*1000, 1, '.', ' ').' ms',
						number_format(($time - $this->DEBUG_last_time)*1000, 1, '.', ' ').' ms',
						number_format($usage/1024, 1, '.', ' ').' Ko',
						number_format($peak/1024, 1, '.', ' ').' Ko');
						
			$this->DEBUG_last_time = $time;
			if ($level===false) $this->DEBUG_level--;
			return true;
		}
		
		/**
		* affiche une ligne de debug
		*
		* @param	string	nom de l'etape
		* @param	string	valeur 1
		* @param	string	valeur 2
		* @param	string	valeur 3
		* @param	string	valeur 4
		* @return	null
		*/
		protected function DEBUG_stepline($nom, $val1, $val2, $val3, $val4)
		{
			$txt = str_pad($nom, 30, ' ', STR_PAD_RIGHT).
					str_pad($val1, 12, ' ', STR_PAD_LEFT).
					str_pad($val2, 12, ' ', STR_PAD_LEFT).
					str_pad($val3, 15, ' ', STR_PAD_LEFT).
					str_pad($val4, 15, ' ', STR_PAD_LEFT);
			
			echo '<pre style="padding:0; margin:0">'.$txt.'</pre>';
		}
		
		/**
		* activer ou desactiver le test de TD ne devant pas depasser une page
		*
		* @param	boolean	nouvel etat
		* @return	boolean ancien etat
		*/
		public function setTestTdInOnePage($mode = true)
		{
			$old = $this->testTDin1page;
			$this->testTDin1page = $mode ? true : false;
			return $old;
		}
		
		/**
		* activer ou desactiver le test sur la pr�sence des images
		*
		* @param	boolean	nouvel etat
		* @return	boolean ancien etat
		*/
		public function setTestIsImage($mode = true)
		{
			$old = $this->testIsImage;
			$this->testIsImage = $mode ? true : false;
			return $old;
		}
		
		/**
		* activer ou desactiver le test sur les fonctions deprecated
		*
		* @param	boolean	nouvel etat
		* @return	boolean ancien etat
		*/
		public function setTestIsDeprecated($mode = true)
		{
			$old = $this->testIsDeprecated;
			$this->testIsDeprecated = $mode ? true : false;
			return $old;
		}

		/**
		* d�finit la fonte par d�faut si aucun fonte n'est sp�cifi�e, ou si la fonte demand�e n'existe pas
		*
		* @param	string	nom de la fonte par defaut. si null : Arial pour fonte non sp�cifi�e, et erreur pour fonte non existante 
		* @return	string	nom de l'ancienne fonte par defaut
		*/
		public function setDefaultFont($default = null)
		{
			$old = $this->defaultFont;
			$this->defaultFont = $default;
			$this->style->setDefaultFont($default);
			return $old;
		}
			
		/**
		* d�finir les marges par d�fault
		*
		* @param	int		en mm, marge left
		* @param	int		en mm, marge top
		* @param	int		en mm, marge right. si null, left=right
		* @param	int		en mm, marge bottom. si null, bottom=8
		* @return	null
		*/
		protected function setDefaultMargins($left, $top, $right = null, $bottom = null)
		{
			if ($right===null)	$right = $left;
			if ($bottom===null)	$bottom = 8;
			
			$this->defaultLeft		= $this->style->ConvertToMM($left.'mm');
			$this->defaultTop		= $this->style->ConvertToMM($top.'mm');
			$this->defaultRight		= $this->style->ConvertToMM($right.'mm');
			$this->defaultBottom	= $this->style->ConvertToMM($bottom.'mm');
		}

		/**
		* d�finir les marges r�elles, fonctions de la balise page
		*
		* @return	null
		*/
		protected function setMargins()
		{
			$this->margeLeft	= $this->defaultLeft	+ (isset($this->background['left'])		? $this->background['left']		: 0);
			$this->margeRight	= $this->defaultRight	+ (isset($this->background['right'])	? $this->background['right']	: 0);
			$this->margeTop		= $this->defaultTop 	+ (isset($this->background['top'])		? $this->background['top']		: 0);
			$this->margeBottom	= $this->defaultBottom	+ (isset($this->background['bottom'])	? $this->background['bottom']	: 0);
			
			$this->pdf->SetMargins($this->margeLeft, $this->margeTop, $this->margeRight);
			$this->pdf->setcMargin(0);
			$this->pdf->SetAutoPageBreak(false, $this->margeBottom);
			
			$this->pageMarges = array();
			$this->pageMarges[floor($this->margeTop*100)] = array($this->margeLeft, $this->pdf->getW()-$this->margeRight);
		}
		
		/**
		* recuperer les positions x minimales et maximales en fonction d'une hauteur
		*
		* @param	float	y
		* @return	array(float, float)
		*/
		protected function getMargins($y)
		{
			$y = floor($y*100);
			$x = array($this->pdf->getlMargin(), $this->pdf->getW()-$this->pdf->getrMargin());
			
			foreach($this->pageMarges as $m_y => $m_x)
				if ($m_y<=$y) $x = $m_x;
			
			return $x;
		}
		
		/**
		* ajouter une marge suite a un float
		*
		* @param	string	left ou right
		* @param	float	x1
		* @param	float	y1
		* @param	float	x2
		* @param	float	y2
		* @return	null
		*/
		protected function addMargins($float, $x1, $y1, $x2, $y2)
		{
			$old1 = $this->getMargins($y1);
			$old2 = $this->getMargins($y2);
			if ($float=='left') $old1[0] = $x2;
			if ($float=='right') $old1[1] = $x1;
			
			$y1 = floor($y1*100);
			$y2 = floor($y2*100);

			foreach($this->pageMarges as $m_y => $m_x)
			{
				if ($m_y<$y1) continue;				
				if ($m_y>$y2) break;	
				if ($float=='left' && $this->pageMarges[$m_y][0]<$x2) unset($this->pageMarges[$m_y]);
				if ($float=='right' && $this->pageMarges[$m_y][1]>$x1) unset($this->pageMarges[$m_y]);
			}

			$this->pageMarges[$y1] = $old1;
			$this->pageMarges[$y2] = $old2;
			
			ksort($this->pageMarges);
			
			$this->isAfterFloat = true;
		}
	
		/**
		* d�finir des nouvelles marges et sauvegarder les anciennes
		*
		* @param	float	marge left
		* @param	float	marge top
		* @param	float	marge right
		* @return	null
		*/
		protected function saveMargin($ml, $mt, $mr)
		{
			$this->marges[] = array('l' => $this->pdf->getlMargin(), 't' => $this->pdf->gettMargin(), 'r' => $this->pdf->getrMargin(), 'page' => $this->pageMarges);
			$this->pdf->SetMargins($ml, $mt, $mr);

			$this->pageMarges = array();
			$this->pageMarges[floor($mt*100)] = array($ml, $this->pdf->getW()-$mr);
		}
		
		/**
		* r�cuperer les derni�res marches sauv�es
		*
		* @return	null
		*/
		protected function loadMargin()
		{
			$old = array_pop($this->marges);
			if ($old)
			{
				$ml = $old['l'];
				$mt = $old['t'];
				$mr = $old['r'];
				$mP = $old['page'];
			}
			else
			{
				$ml = $this->margeLeft;
				$mt = 0;
				$mr = $this->margeRight;
				$mP = array($mt => array($ml, $this->pdf->getW()-$mr));
			}
			
			$this->pdf->SetMargins($ml, $mt, $mr);
			$this->pageMarges = $mP;
		}
		
		/**
		* permet d'ajouter une fonte.
		*
		* @param	string nom de la fonte
		* @param	string style de la fonte
		* @param	string fichier de la fonte
		* @return	null
		*/
		public function addFont($family, $style='', $file='')
		{
			$this->pdf->AddFont($family, $style, $file);
		}
		
		/**
		* sauvegarder l'�tat actuelle des maximums
		*
		* @return	null
		*/
		protected function saveMax()
		{
			$this->maxSave[] = array($this->maxX, $this->maxY, $this->maxH, $this->maxE);
		}
				
		/**
		* charger le dernier �tat sauv� des maximums
		*
		* @return	null
		*/
		protected function loadMax()
		{
			$old = array_pop($this->maxSave);

			if ($old)
			{
				$this->maxX = $old[0];
				$this->maxY = $old[1];
				$this->maxH = $old[2];
				$this->maxE = $old[3];
			}
			else
			{
				$this->maxX = 0;
				$this->maxY = 0;
				$this->maxH = 0;
				$this->maxE = 0;
			}
		}
		
		/**
		* afficher l'header contenu dans page_header
		*
		* @return	null
		*/
		protected function setPageHeader()
		{
			if (!count($this->subHEADER)) return false;

			$OLD_parse_pos = $this->parse_pos;
			$OLD_parse_code = $this->parsing->code;
			
			$this->parse_pos = 0;
			$this->parsing->code = $this->subHEADER;
			$this->makeHTMLcode();
			
			$this->parse_pos = 	$OLD_parse_pos;
			$this->parsing->code = $OLD_parse_code;
		}

		/**
		* afficher le footer contenu dans page_footer
		*
		* @return	null
		*/
		protected function setPageFooter()
		{
			if (!count($this->subFOOTER)) return false;

			$OLD_parse_pos = $this->parse_pos;
			$OLD_parse_code = $this->parsing->code;
			
			$this->parse_pos = 0;
			$this->parsing->code = $this->subFOOTER;
			$this->isInFooter = true;
			$this->makeHTMLcode();
			$this->isInFooter = false;
			
			$this->parse_pos = 	$OLD_parse_pos;
			$this->parsing->code = $OLD_parse_code;
		}
		
		/**
		* saut de ligne avec une hauteur sp�cifique
		*
		* @param	float	hauteur de la ligne
		* @param	integer	position reelle courante si saut de ligne pendant l'ecriture d'un texte 
		* @return	null
		*/
		protected function setNewLine($h, $curr = null)
		{
			$this->pdf->Ln($h);
			
			$this->setNewPositionForNewLine($curr);
		}
			
		/**
		* cr�ation d'une nouvelle page avec le format et l'orientation sp�cifies
		*
		* @param	mixed	format de la page : A5, A4, array(width, height)
		* @param	string	sens P=portrait ou L=landscape
		* @param	array	tableau des propri�t�s du fond de la page
		* @param	integer	position reelle courante si saut de ligne pendant l'ecriture d'un texte 
		* @return	null
		*/
		public function setNewPage($format = null, $orientation = '', $background = null, $curr = null)
		{
			$this->firstPage = false;

			$this->format = $format ? $format : $this->format;
			$this->sens = $orientation ? $orientation : $this->sens;
			$this->background = $background!==null ? $background : $this->background;
			$this->maxY = 0;	
			$this->maxX = 0;
			$this->maxH = 0;
			
			$this->pdf->SetMargins($this->defaultLeft, $this->defaultTop, $this->defaultRight);
			$this->pdf->AddPage($this->sens, $this->format);
			$this->page++;
			
			if (!$this->sub_part && !$this->isSubPart)
			{
				if (is_array($this->background))
				{
					if (isset($this->background['color']) && $this->background['color'])
					{
						$this->pdf->setFillColorArray($this->background['color']);
						$this->pdf->Rect(0, 0, $this->pdf->getW(), $this->pdf->getH(), 'F');
					}

					if (isset($this->background['img']) && $this->background['img'])
						$this->pdf->Image($this->background['img'], $this->background['posX'], $this->background['posY'], $this->background['width']);
				}	
				
				$this->setPageHeader();
				$this->setPageFooter();
			}
			
			$this->setMargins();
			$this->pdf->setY($this->margeTop);
			
			$this->setNewPositionForNewLine($curr);
			$this->maxH = 0;
		}
		
		/**
		* calcul de la position de debut de la prochaine ligne en fonction de l'alignement voulu
		*
		* @param	integer	position reelle courante si saut de ligne pendant l'ecriture d'un texte 
		* @return	null
		*/
		protected function setNewPositionForNewLine($curr = null)
		{
			list($lx, $rx) = $this->getMargins($this->pdf->getY());
			$this->pdf->setX($lx);
			$wMax = $rx-$lx;
			$this->currentH = 0;
			
			if ($this->sub_part || $this->isSubPart || $this->forOneLine)
			{
//				$this->pdf->setWordSpacing(0);
				return null;
			}
/*
			if (
				$this->style->value['text-align']!='right' && 
				$this->style->value['text-align']!='center' && 
				$this->style->value['text-align']!='justify'
				)
			{
//				$this->pdf->setWordSpacing(0);
				return null;
			}
*/			
			$sub = null;
			$this->createSubHTML($sub);
			$sub->saveMargin(0, 0, $sub->pdf->getW()-$wMax);
			$sub->forOneLine = true;
			$sub->parse_pos = $this->parse_pos;
			$sub->parsing->code = $this->parsing->code;
			
			if ($curr!==null && $sub->parsing->code[$this->parse_pos]['name']=='write')
			{
				$txt = $sub->parsing->code[$this->parse_pos]['param']['txt'];
				$txt = str_replace('[[page_cu]]',	$sub->page,	$txt);
				$sub->parsing->code[$this->parse_pos]['param']['txt'] = substr($txt, $curr);
			}
			else
				$sub->parse_pos++;
				
			// pour chaque element identifi� par le parsing
			$res = null;
			for($sub->parse_pos; $sub->parse_pos<count($sub->parsing->code); $sub->parse_pos++)
			{
				$todo = $sub->parsing->code[$sub->parse_pos];
				$res = $sub->loadAction($todo);
				if (!$res) break;
			}

			$w = $sub->maxX; // largeur maximale
			$h = $sub->maxH; // hauteur maximale
			$e = ($res===null ? $sub->maxE : 0); // nombre d'�l�ments maximal
			$this->destroySubHTML($sub);
			
			if ($this->style->value['text-align']=='center')
				$this->pdf->setX(($rx+$this->pdf->getX()-$w)*0.5-0.01);
			elseif ($this->style->value['text-align']=='right')
				$this->pdf->setX($rx-$w-0.01);
			else
				$this->pdf->setX($lx);
			
			$this->currentH = $h;
/*				
			if ($this->style->value['text-align']=='justify' && $e>1)
				$this->pdf->setWordSpacing(($wMax-$w)/($e-1));
			else
				$this->pdf->setWordSpacing(0);
*/
		}
		
		/** 
		* r�cup�ration du PDF 
		* 
		* @param	string	nom du fichier PDF 
		* @param	boolean	destination 
		* @return	string	contenu �ventuel du pdf
		* 
		*
		* Destination o� envoyer le document. Le param�tre peut prendre les valeurs suivantes :
		* true	: equivalent � I
		* false	: equivalent � S
		* I : envoyer en inline au navigateur. Le plug-in est utilis� s'il est install�. Le nom indiqu� dans name est utilis� lorsque l'on s�lectionne "Enregistrer sous" sur le lien g�n�rant le PDF.
		* D : envoyer au navigateur en for�ant le t�l�chargement, avec le nom indiqu� dans name.
		* F : sauver dans un fichier local, avec le nom indiqu� dans name (peut inclure un r�pertoire).
		* S : renvoyer le document sous forme de cha�ne. name est ignor�.
		*/
		public function Output($name = '', $dest = false)
		{
			// nettoyage
			HTML2PDF::$TABLES = array();

			if ($this->DEBUG_actif)
			{
				$this->DEBUG_add('Before output');
				$this->pdf->Close();
				exit;
			}
			
			// interpretation des param�tres
			if ($dest===false)	$dest = 'I';
			if ($dest===true)	$dest = 'S';
			if ($dest==='')		$dest = 'I';
			if ($name=='')		$name='document.pdf';
			
			// verification de la destination
			$dest = strtoupper($dest);
			if (!in_array($dest, array('I', 'D', 'F', 'S'))) $dest = 'I';
	
			// verification du nom
			if (strtolower(substr($name, -4))!='.pdf')
			{
				echo 'ERROR : The output document name "'.$name.'" is not a PDF name';
				exit;
			}
			
			return $this->pdf->Output($name, $dest);
		}
		
		/**
		* preparation de HTML2PDF::$SUBOBJ utilis� pour la cr�ation des sous HTML2PDF
		*
		* @return	null
		*/
		protected function prepareSubObj()
		{
			$pdf = null;
			
			HTML2PDF::$SUBOBJ = new HTML2PDF(
										$this->sens,
										$this->format,
										$this->langue,
										$this->unicode,
										$this->encoding,
										array($this->defaultLeft,$this->defaultTop,$this->defaultRight,$this->defaultBottom)
									);

			// initialisation
			HTML2PDF::$SUBOBJ->setIsSubPart();
			HTML2PDF::$SUBOBJ->setTestTdInOnePage($this->testTDin1page);
			HTML2PDF::$SUBOBJ->setTestIsImage($this->testIsImage);
			HTML2PDF::$SUBOBJ->setTestIsDeprecated($this->testIsDeprecated);
			HTML2PDF::$SUBOBJ->setDefaultFont($this->defaultFont);
			HTML2PDF::$SUBOBJ->style->css			= &$this->style->css;
			HTML2PDF::$SUBOBJ->style->css_keys		= &$this->style->css_keys;
			HTML2PDF::$SUBOBJ->pdf->cloneFontFrom($this->pdf);
			HTML2PDF::$SUBOBJ->style->setPdfParent($pdf);
		}
			
		/**
		* fonction de clonage pour la creation d'un sous HTML2PDF � partir de HTML2PDF::$SUBOBJ
		*
		* @return	null
		*/	
		public function __clone()
		{
			$this->pdf		= clone $this->pdf;
			$this->parsing	= clone $this->parsing;
			$this->style	= clone $this->style;
			$this->style->setPdfParent($this->pdf);
		}
		
		/**
		* cr�ation d'un sous HTML2PDF pour la gestion des tableaux imbriqu�s
		*
		* @param	HTML2PDF	futur sous HTML2PDF pass� en r�f�rence pour cr�ation
		* @param	integer		marge eventuelle de l'objet si simulation d'un TD
		* @return	null
		*/		
		protected function createSubHTML(&$sub_html, $cellmargin=0)
		{
			if (!HTML2PDF::$SUBOBJ) $this->prepareSubObj();
			
			// calcul de la largueur
			if ($this->style->value['width'])
			{
				$marge = $cellmargin*2;
				$marge+= $this->style->value['padding']['l'] + $this->style->value['padding']['r'];
				$marge+= $this->style->value['border']['l']['width'] + $this->style->value['border']['r']['width'];
				$marge = $this->pdf->getW() - $this->style->value['width'] + $marge;
			}
			else
				$marge = $this->margeLeft+$this->margeRight;
			
			//clonage
			$sub_html = clone HTML2PDF::$SUBOBJ;
			$sub_html->style->table			= $this->style->table;
			$sub_html->style->value			= $this->style->value;
			$sub_html->style->setOnlyLeft();
			$sub_html->setNewPage($this->format, $this->sens);
			$sub_html->initSubHtml($marge, $this->page, $this->defLIST);
		}
		
		/**
		* initialise le sous HTML2PDF. Ne pas utiliser directement. seul la fonction createSubHTML doit l'utiliser
		*
		* @return	null
		*/	
		public function initSubHtml($marge, $page, $defLIST)
		{
			$this->saveMargin(0, 0, $marge);
			$this->defLIST = $defLIST;
			
			$this->page = $page;
			$this->pdf->setXY(0, 0);
			$this->style->FontSet();
		}
		
		public function setIsSubPart()
		{
			$this->isSubPart = true;
		}
		
		/**
		* destruction d'un sous HTML2PDF pour la gestion des tableaux imbriqu�s
		*
		* @return	null
		*/	
		protected function destroySubHTML(&$sub_html)
		{
			unset($sub_html);
			$sub_html = null;	
		}
		
		/**
		* Convertir un nombre arabe en nombre romain
		*
		* @param	integer	nombre � convertir
		* @return	string	nombre converti
		*/
		protected function listeArab2Rom($nb_ar)
		{
			$nb_b10	= array('I','X','C','M');
			$nb_b5	= array('V','L','D');
			$nb_ro	= '';

			if ($nb_ar<1)		return $nb_ar;
			if ($nb_ar>3999)	return $nb_ar;

			for($i=3; $i>=0 ; $i--)
			{
				$chiffre=floor($nb_ar/pow(10,$i));
				if($chiffre>=1)
				{
					$nb_ar=$nb_ar-$chiffre*pow(10,$i);
					if($chiffre<=3)
					{
						for($j=$chiffre; $j>=1; $j--)
						{
							$nb_ro=$nb_ro.$nb_b10[$i];
						}
					}
					else if($chiffre==9)
					{
						$nb_ro=$nb_ro.$nb_b10[$i].$nb_b10[$i+1];
					}
					elseif($chiffre==4)
					{
					$nb_ro=$nb_ro.$nb_b10[$i].$nb_b5[$i];
					}
					else
					{
						$nb_ro=$nb_ro.$nb_b5[$i];
						for($j=$chiffre-5; $j>=1; $j--)
						{
							$nb_ro=$nb_ro.$nb_b10[$i];
						}
					}
				}
			}
			return $nb_ro;
		}
		
		/**
		* Ajouter un LI au niveau actuel
		*
		* @return	null
		*/
		protected function listeAddLi()
		{
			$this->defLIST[count($this->defLIST)-1]['nb']++;
		}

		protected function listeGetWidth() { return '7mm'; }
		protected function listeGetPadding() { return '1mm'; }

		/**
		* Recuperer le LI du niveau actuel
		*
		* @return	string	chaine � afficher
		*/
		protected function listeGetLi()
		{
			$im = $this->defLIST[count($this->defLIST)-1]['img'];
			$st = $this->defLIST[count($this->defLIST)-1]['style'];
			$nb = $this->defLIST[count($this->defLIST)-1]['nb'];
			$up = (substr($st, 0, 6)=='upper-');
			
			if ($im) return array(false, false, $im);
			
			switch($st)
			{
				case 'none':
					return array('helvetica', true, ' ');
					
				case 'upper-alpha':
				case 'lower-alpha':
					$str = '';
					while($nb>26)
					{
						$str = chr(96+$nb%26).$str; 
						$nb = floor($nb/26);	
					}
					$str = chr(96+$nb).$str; 
					
					return array('helvetica', false, ($up ? strtoupper($str) : $str).'.');

				case 'upper-roman':
				case 'lower-roman':
					$str = $this->listeArab2Rom($nb);
					
					return array('helvetica', false, ($up ? strtoupper($str) : $str).'.');
					
				case 'decimal':
					return array('helvetica', false, $nb.'.');

				case 'square':
					return array('zapfdingbats', true, chr(110));

				case 'circle':
					return array('zapfdingbats', true, chr(109));

				case 'disc':
				default:
					return array('zapfdingbats', true, chr(108));
			}
		}
				
		/**
		* Ajouter un niveau de liste
		*
		* @param	string	type de liste : ul, ol
		* @param	string	style de la liste
		* @return	null
		*/
		protected function listeAddLevel($type = 'ul', $style = '', $img = null)
		{
			if ($img)
			{
				if (preg_match('/^url\(([^)]+)\)$/isU', trim($img), $match))
					$img = $match[1];
				else
					$img = null;
			}
			else
				$img = null;
			
			if (!in_array($type, array('ul', 'ol'))) $type = 'ul';
			if (!in_array($style, array('lower-alpha', 'upper-alpha', 'upper-roman', 'lower-roman', 'decimal', 'square', 'circle', 'disc', 'none'))) $style = '';
			
			if (!$style)
			{
				if ($type=='ul')	$style = 'disc';
				else				$style = 'decimal';
			}
			$this->defLIST[count($this->defLIST)] = array('style' => $style, 'nb' => 0, 'img' => $img);
		}
		
		/**
		* Supprimer un niveau de liste
		*
		* @return	null
		*/
		protected function listeDelLevel()
		{
			if (count($this->defLIST))
			{
				unset($this->defLIST[count($this->defLIST)-1]);
				$this->defLIST = array_values($this->defLIST);
			}
		}
		
		/**
		* traitement d'un code HTML fait pour HTML2PDF
		*
		* @param	string	code HTML � convertir
		* @param	boolean	afficher en pdf (false) ou en html adapt� (true)
		* @return	null
		*/
		public function writeHTML($html, $vue = false)
		{
			// si c'est une vrai page HTML, une conversion s'impose
			if (preg_match('/<body/isU', $html))
				$html = $this->getHtmlFromPage($html);
				
			$html = str_replace('[[page_nb]]',	'{nb}',	 $html);
			
			$html = str_replace('[[date_y]]',	date('Y'),	 $html);
			$html = str_replace('[[date_m]]',	date('m'),	 $html);
			$html = str_replace('[[date_d]]',	date('d'),	 $html);

			$html = str_replace('[[date_h]]',	date('H'),	 $html);
			$html = str_replace('[[date_i]]',	date('i'),	 $html);
			$html = str_replace('[[date_s]]',	date('s'),	 $html);
			
			// si on veut voir le r�sultat en HTML => on appelle la fonction
			if ($vue)	$this->vueHTML($html);	

			// sinon, traitement pour conversion en PDF :
			// parsing
			$this->sub_pdf = false;
			$this->style->readStyle($html);
			$this->parsing->setHTML($html);
			$this->parsing->parse();
			$this->makeHTMLcode();
		}
			
		/**
		* traitement du code d'une vrai page HTML pour l'adapter � HTML2PDF
		*
		* @param	string	code HTML � adapter
		* @return	string	code HTML adapt�
		*/
		public function getHtmlFromPage($html)
		{
			$html = str_replace('<BODY', '<body', $html);
			$html = str_replace('</BODY', '</body', $html);
			
			// extraction du contenu
			$res = explode('<body', $html);
			if (count($res)<2) return $html;
			$content = '<page'.$res[1];
			$content = explode('</body', $content);
			$content = $content[0].'</page>';

			// extraction des balises link
			preg_match_all('/<link([^>]*)>/isU', $html, $match);
			foreach($match[0] as $src)
				$content = $src.'</link>'.$content;	
			
			// extraction des balises style
			preg_match_all('/<style[^>]*>(.*)<\/style[^>]*>/isU', $html, $match);
			foreach($match[0] as $src)
				$content = $src.$content;	
						
			return $content;	
		}
		
		/**
		* execute les diff�rentes actions du code HTML
		*
		* @return	null
		*/
		protected function makeHTMLcode()
		{
			// pour chaque element identifi� par le parsing
			for($this->parse_pos=0; $this->parse_pos<count($this->parsing->code); $this->parse_pos++)
			{
				// r�cup�ration de l'�l�ment
				$todo = $this->parsing->code[$this->parse_pos];
				
				// si c'est une ouverture de tableau
				if (in_array($todo['name'], array('table', 'ul', 'ol')) && !$todo['close'])
				{
					// on va cr�er un sous HTML, et on va travailler sur une position temporaire
					$tag_open = $todo['name'];

					$this->sub_part = true;
					$this->temp_pos = $this->parse_pos;
					
					// pour tous les �l�ments jusqu'� la fermeture de la table afin de pr�parer les dimensions
					while(isset($this->parsing->code[$this->temp_pos]) && !($this->parsing->code[$this->temp_pos]['name']==$tag_open && $this->parsing->code[$this->temp_pos]['close']))
					{
						$this->loadAction($this->parsing->code[$this->temp_pos]);
						$this->temp_pos++;
					}
					if (isset($this->parsing->code[$this->temp_pos])) 	$this->loadAction($this->parsing->code[$this->temp_pos]);
					$this->sub_part = false;
				}
				
				// chargement de l'action correspondant � l'�l�ment
				$this->loadAction($todo);
			}
		} 


	
		/**
		* affichage en mode HTML du contenu
		*
		* @param	string	contenu
		* @return	null
		*/	
		protected function vueHTML($content)
		{
			$content = preg_replace('/<page_header([^>]*)>/isU',	'<hr>'.HTML2PDF::textGET('vue01').' : $1<hr><div$1>', $content);
			$content = preg_replace('/<page_footer([^>]*)>/isU',	'<hr>'.HTML2PDF::textGET('vue02').' : $1<hr><div$1>', $content);
			$content = preg_replace('/<page([^>]*)>/isU',			'<hr>'.HTML2PDF::textGET('vue03').' : $1<hr><div$1>', $content);
			$content = preg_replace('/<\/page([^>]*)>/isU',			'</div><hr>', $content);
			$content = preg_replace('/<bookmark([^>]*)>/isU',		'<hr>bookmark : $1<hr>', $content);
			$content = preg_replace('/<\/bookmark([^>]*)>/isU',		'', $content);
			$content = preg_replace('/<barcode([^>]*)>/isU',		'<hr>barcode : $1<hr>', $content);
			$content = preg_replace('/<\/barcode([^>]*)>/isU',		'', $content);
			$content = preg_replace('/<qrcode([^>]*)>/isU',		'<hr>qrcode : $1<hr>', $content);
			$content = preg_replace('/<\/qrcode([^>]*)>/isU',		'', $content);
			
			echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<title>'.HTML2PDF::textGET('vue04').' HTML</title>
		<meta http-equiv="Content-Type" content="text/html; charset='.$this->encoding.'" >
	</head>
	<body style="padding: 10px; font-size: 10pt;font-family:	Verdana;">
'.$content.'
	</body>
</html>';
			exit;	
		}

		/**
		* chargement de l'action correspondante � un element de parsing
		*
		* @param	array	�l�ment de parsing
		* @return	null
		*/		
		protected function loadAction($row)
		{
			// nom de l'action
			$fnc	= ($row['close'] ? 'c_' : 'o_').strtoupper($row['name']);
			
			// parametres de l'action
			$param	= $row['param'];
			
			// si aucune page n'est cr��, on la cr��
			if ($fnc!='o_PAGE' && $this->firstPage)
			{
				$this->setNewPage();
			}
			
			// lancement de l'action
			if (is_callable(array(&$this, $fnc)))
			{
				$res = $this->{$fnc}($param);
				$this->previousCall = $fnc;
				return $res;
			}
			else
				throw new HTML2PDF_exception(1, strtoupper($row['name']), $this->parsing->getHtmlErrorCode($row['html_pos']));
		}
		
		/**
		* balise	: PAGE
		* mode		: OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_PAGE($param)
		{
			if ($this->forOneLine) return false;
			if ($this->DEBUG_actif) $this->DEBUG_add('PAGE n�'.($this->page+1), true);

			$newPageSet= (!isset($param['pageset']) || $param['pageset']!='old');
			
			$this->maxH = 0;
			if ($newPageSet)
			{
				$this->subHEADER = array();
				$this->subFOOTER = array();
						
				// identification de l'orientation demand�e
				$orientation = '';
				if (isset($param['orientation']))
				{
					$param['orientation'] = strtolower($param['orientation']);
					if ($param['orientation']=='p')			$orientation = 'P';
					if ($param['orientation']=='portrait')	$orientation = 'P';
	
					if ($param['orientation']=='l')			$orientation = 'L';
					if ($param['orientation']=='paysage')	$orientation = 'L';
					if ($param['orientation']=='landscape')	$orientation = 'L';
				}
				
				// identification de l'orientation demand�e
				$format = null;
				if (isset($param['format']))
				{
					$format = strtolower($param['format']);
					if (preg_match('/^([0-9]+)x([0-9]+)$/isU', $format, $match))
					{
						$format = array(intval($match[1]), intval($match[2]));
					}
				}
					
				// identification des propri�t�s du background
				$background = array();
				if (isset($param['backimg']))
				{
					$background['img']		= isset($param['backimg'])	? $param['backimg']		: '';		// nom de l'image
					$background['posX']		= isset($param['backimgx'])	? $param['backimgx']	: 'center'; // position horizontale de l'image
					$background['posY']		= isset($param['backimgy'])	? $param['backimgy']	: 'middle'; // position verticale de l'image
					$background['width']	= isset($param['backimgw'])	? $param['backimgw']	: '100%';	// taille de l'image (100% = largueur de la feuille)
					
					// conversion du nom de l'image, en cas de param�tres en _GET
					$background['img'] = str_replace('&amp;', '&', $background['img']);
					// conversion des positions
					if ($background['posX']=='left')	$background['posX'] = '0%';
					if ($background['posX']=='center')	$background['posX'] = '50%';
					if ($background['posX']=='right')	$background['posX'] = '100%';
					if ($background['posY']=='top')		$background['posY'] = '0%';
					if ($background['posY']=='middle')	$background['posY'] = '50%';
					if ($background['posY']=='bottom')	$background['posY'] = '100%';
	
	
					// si il y a une image de pr�cis�
					if ($background['img'])	
					{
						// est-ce que c'est une image ?
						$infos=@GetImageSize($background['img']);
						if (count($infos)>1)
						{
							// taille de l'image, en fonction de la taille sp�cifi�e. 
							$Wi = $this->style->ConvertToMM($background['width'], $this->pdf->getW());
							$Hi = $Wi*$infos[1]/$infos[0];
							
							// r�cup�ration des dimensions et positions de l'image
							$background['width']	= $Wi;	
							$background['posX']		= $this->style->ConvertToMM($background['posX'], $this->pdf->getW() - $Wi);
							$background['posY']		= $this->style->ConvertToMM($background['posY'], $this->pdf->getH() - $Hi);
						}
						else
							$background = array();	
					}
					else
						$background = array();
				}
				
				// marges TOP et BOTTOM pour le texte.
				$background['top']		= isset($param['backtop'])			? $param['backtop'] 		: '0';
				$background['bottom']	= isset($param['backbottom'])		? $param['backbottom']		: '0';
				$background['left']		= isset($param['backleft'])			? $param['backleft'] 		: '0';
				$background['right']	= isset($param['backright'])		? $param['backright']		: '0';

				if (preg_match('/^([0-9]*)$/isU', $background['top']))		$background['top']		.= 'mm';
				if (preg_match('/^([0-9]*)$/isU', $background['bottom']))	$background['bottom']	.= 'mm';
				if (preg_match('/^([0-9]*)$/isU', $background['left']))		$background['left']		.= 'mm';
				if (preg_match('/^([0-9]*)$/isU', $background['right']))	$background['right']	.= 'mm';

				$background['top']		= $this->style->ConvertToMM($background['top'],		$this->pdf->getH());
				$background['bottom']	= $this->style->ConvertToMM($background['bottom'],	$this->pdf->getH());
				$background['left']		= $this->style->ConvertToMM($background['left'],	$this->pdf->getW());
				$background['right']	= $this->style->ConvertToMM($background['right'],	$this->pdf->getW());

				$res = false;
				$background['color']	= isset($param['backcolor'])	? $this->style->ConvertToColor($param['backcolor'], $res) : null;
				if (!$res) $background['color'] = null;

				$this->style->save();
				$this->style->analyse('PAGE', $param);
				$this->style->setPosition();
				$this->style->FontSet();
				
				// nouvelle page
				$this->setNewPage($format, $orientation, $background);
	
				// footer automatique
				if (isset($param['footer']))
				{
					$lst = explode(';', $param['footer']);
					foreach($lst as $key => $val) $lst[$key] = trim(strtolower($val));
					$page	= in_array('page', $lst);
					$date	= in_array('date', $lst);
					$heure	= in_array('heure', $lst);
					$form	= in_array('form', $lst);
				}
				else
				{
					$page	= null;
					$date	= null;
					$heure	= null;
					$form	= null;
				}
				$this->pdf->SetMyFooter($page, $date, $heure, $form);
			}
			else
			{
				$this->style->save();
				$this->style->analyse('PAGE', $param);
				$this->style->setPosition();
				$this->style->FontSet();
				
				$this->setNewPage();
			}			
			
			return true;
		}

		/**
		* balise : PAGE
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_PAGE($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;

			$this->style->load();
			$this->style->FontSet();
			
			if ($this->DEBUG_actif) $this->DEBUG_add('PAGE n�'.$this->page, false);
			
			return true;
		}
		
		
		protected function o_PAGE_HEADER($param)
		{
			if ($this->forOneLine) return false;

			$this->subHEADER = array();
			for($this->parse_pos; $this->parse_pos<count($this->parsing->code); $this->parse_pos++)
			{
				$todo = $this->parsing->code[$this->parse_pos];
				if ($todo['name']=='page_header') $todo['name']='page_header_sub';
				$this->subHEADER[] = $todo;
				if (strtolower($todo['name'])=='page_header_sub' && $todo['close']) break;
			}

			$this->setPageHeader();
			
			return true;
		}
		
		protected function o_PAGE_FOOTER($param)
		{
			if ($this->forOneLine) return false;

			$this->subFOOTER = array();
			for($this->parse_pos; $this->parse_pos<count($this->parsing->code); $this->parse_pos++)
			{
				$todo = $this->parsing->code[$this->parse_pos];
				if ($todo['name']=='page_footer') $todo['name']='page_footer_sub';
				$this->subFOOTER[] = $todo;
				if (strtolower($todo['name'])=='page_footer_sub' && $todo['close']) break;
			}
			
			$this->setPageFooter();
			
			return true;
		}

		protected function o_PAGE_HEADER_SUB($param)
		{
			if ($this->forOneLine) return false;

			// sauvegarde de l'�tat
			$this->subSTATES = array();
			$this->subSTATES['x']	= $this->pdf->getX();
			$this->subSTATES['y']	= $this->pdf->getY();
			$this->subSTATES['s']	= $this->style->value;
			$this->subSTATES['t']	= $this->style->table;
			$this->subSTATES['ml']	= $this->margeLeft;
			$this->subSTATES['mr']	= $this->margeRight;
			$this->subSTATES['mt']	= $this->margeTop;
			$this->subSTATES['mb']	= $this->margeBottom;
			$this->subSTATES['mp']	= $this->pageMarges;
	
			// nouvel etat pour le footer
			$this->pageMarges = array();
			$this->margeLeft	= $this->defaultLeft;
			$this->margeRight	= $this->defaultRight;
			$this->margeTop		= $this->defaultTop;
			$this->margeBottom	= $this->defaultBottom;
			$this->pdf->SetMargins($this->margeLeft, $this->margeTop, $this->margeRight);
			$this->pdf->SetAutoPageBreak(false, $this->margeBottom);
			$this->pdf->setXY($this->defaultLeft, $this->defaultTop);
			
			$this->style->initStyle();
			$this->style->resetStyle();
			$this->style->value['width']	= $this->pdf->getW() - $this->defaultLeft - $this->defaultRight;
			$this->style->table				= array();

			$this->style->save();
			$this->style->analyse('page_header_sub', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			$this->setNewPositionForNewLine();				
			return true;
		}

		protected function c_PAGE_HEADER_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();

			// retablissement de l'etat
			$this->style->value				= $this->subSTATES['s'];
			$this->style->table				= $this->subSTATES['t'];
			$this->pageMarges				= $this->subSTATES['mp'];
			$this->margeLeft				= $this->subSTATES['ml'];
			$this->margeRight				= $this->subSTATES['mr'];
			$this->margeTop					= $this->subSTATES['mt'];
			$this->margeBottom				= $this->subSTATES['mb'];
			$this->pdf->SetMargins($this->margeLeft, $this->margeTop, $this->margeRight);
			$this->pdf->setbMargin($this->margeBottom);
			$this->pdf->SetAutoPageBreak(false, $this->margeBottom);
			$this->pdf->setXY($this->subSTATES['x'], $this->subSTATES['y']);
			
			$this->style->FontSet();			
			$this->maxH = 0;		
			
			return true;
		}
				
		protected function o_PAGE_FOOTER_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->subSTATES = array();
			$this->subSTATES['x']	= $this->pdf->getX();
			$this->subSTATES['y']	= $this->pdf->getY();
			$this->subSTATES['s']	= $this->style->value;
			$this->subSTATES['t']	= $this->style->table;
			$this->subSTATES['ml']	= $this->margeLeft;
			$this->subSTATES['mr']	= $this->margeRight;
			$this->subSTATES['mt']	= $this->margeTop;
			$this->subSTATES['mb']	= $this->margeBottom;
			$this->subSTATES['mp']	= $this->pageMarges;
	
			// nouvel etat pour le footer
			$this->pageMarges = array();
			$this->margeLeft	= $this->defaultLeft;
			$this->margeRight	= $this->defaultRight;
			$this->margeTop		= $this->defaultTop;
			$this->margeBottom	= $this->defaultBottom;
			$this->pdf->SetMargins($this->margeLeft, $this->margeTop, $this->margeRight);
			$this->pdf->SetAutoPageBreak(false, $this->margeBottom);
			$this->pdf->setXY($this->defaultLeft, $this->defaultTop);
			
	
			$this->style->initStyle();
			$this->style->resetStyle();
			$this->style->value['width']	= $this->pdf->getW() - $this->defaultLeft - $this->defaultRight;
			$this->style->table				= array();			

			// on en cr�� un sous HTML que l'on transforme en PDF
			// pour r�cup�rer la hauteur
			// on extrait tout ce qui est contenu dans le FOOTER
			$sub = null;
			$this->CreateSubHTML($sub);
			$sub->parsing->code = $this->parsing->getLevel($this->parse_pos);
			$sub->MakeHTMLcode();
			$this->pdf->setY($this->pdf->getH() - $sub->maxY - $this->defaultBottom - 0.01);
			$this->destroySubHTML($sub);
			
			$this->style->save();			
			$this->style->analyse('page_footer_sub', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			$this->setNewPositionForNewLine();		
			
			return true;
		}

		protected function c_PAGE_FOOTER_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();

			$this->style->value				= $this->subSTATES['s'];
			$this->style->table				= $this->subSTATES['t'];
			$this->pageMarges 				= $this->subSTATES['mp'];
			$this->margeLeft				= $this->subSTATES['ml'];
			$this->margeRight				= $this->subSTATES['mr'];
			$this->margeTop					= $this->subSTATES['mt'];
			$this->margeBottom				= $this->subSTATES['mb'];
			$this->pdf->SetMargins($this->margeLeft, $this->margeTop, $this->margeRight);
			$this->pdf->SetAutoPageBreak(false, $this->margeBottom);
			$this->pdf->setXY($this->subSTATES['x'], $this->subSTATES['y']);

			$this->style->FontSet();	
			$this->maxH = 0;		
			
			return true;
		}
		
		/**
		* balise : NOBREAK
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/
		protected function o_NOBREAK($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;

			// on en cr�� un sous HTML que l'on transforme en PDF
			// pour analyse les dimensions
			// et voir si ca rentre
			$sub = null;
			$this->CreateSubHTML($sub);
			$sub->parsing->code = $this->parsing->getLevel($this->parse_pos);
			$sub->MakeHTMLcode();
			
			$y = $this->pdf->getY();
			if (
					$sub->maxY < ($this->pdf->getH() - $this->pdf->gettMargin()-$this->pdf->getbMargin()) &&
					$y + $sub->maxY>=($this->pdf->getH() - $this->pdf->getbMargin())
				)
				$this->setNewPage();
			$this->destroySubHTML($sub);
			
			return true;
		}
		

		/**
		* balise : NOBREAK
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_NOBREAK($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;
					
			return true;
		}
		
		/**
		* balise : DIV
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_DIV($param, $other = 'div')
		{
			if ($this->forOneLine) return false;
			if ($this->DEBUG_actif) $this->DEBUG_add(strtoupper($other), true);
			
			$this->style->save();
			$this->style->analyse($other, $param);
			$this->style->FontSet();
			
			// gestion specifique a la balise legend pour l'afficher au bon endroit
			if (in_array($other, array('fieldset', 'legend')))
			{
				if (isset($param['moveTop']))	$this->style->value['margin']['t']	+= $param['moveTop'];
				if (isset($param['moveLeft']))	$this->style->value['margin']['l']	+= $param['moveLeft'];
				if (isset($param['moveDown']))	$this->style->value['margin']['b']	+= $param['moveDown'];
			}
			
			$align_object = null;
			if ($this->style->value['margin-auto']) $align_object = 'center';

			$marge = array();
			$marge['l'] = $this->style->value['border']['l']['width'] + $this->style->value['padding']['l']+0.03;
			$marge['r'] = $this->style->value['border']['r']['width'] + $this->style->value['padding']['r']+0.03;
			$marge['t'] = $this->style->value['border']['t']['width'] + $this->style->value['padding']['t']+0.03;
			$marge['b'] = $this->style->value['border']['b']['width'] + $this->style->value['padding']['b']+0.03;
			
			// on extrait tout ce qui est contenu dans la DIV
			$level = $this->parsing->getLevel($this->parse_pos);

			// on en cr�� un sous HTML que l'on transforme en PDF
			// pour analyse les dimensions
			$w = 0; $h = 0;
			if (count($level))
			{
				$sub = null;
				$this->CreateSubHTML($sub);
				$sub->parsing->code = $level;
				$sub->MakeHTMLcode();
				$w = $sub->maxX;
				$h = $sub->maxY;
				$this->destroySubHTML($sub);
			}
			$w_reel = $w;
			$h_reel = $h;
			
//			if (($w==0 && $this->style->value['width']==0) || ($w>$this->style->value['width']) || $this->style->value['position']=='absolute')
				$w+= $marge['l']+$marge['r']+0.001;

			$h+= $marge['t']+$marge['b']+0.001;
			
			if ($this->style->value['overflow']=='hidden')
			{
				$over_w = max($w, $this->style->value['width']);
				$over_h = max($h, $this->style->value['height']);
				$overflow = true;
				$this->style->value['old_maxX'] = $this->maxX;
				$this->style->value['old_maxY'] = $this->maxY;
				$this->style->value['old_maxH'] = $this->maxH;
				$this->style->value['old_overflow'] = $this->isInOverflow;
				$this->isInOverflow = true;
			}
			else
			{
				$over_w = null;
				$over_h = null;
				$overflow = false;
				$this->style->value['width']	= max($w, $this->style->value['width']);
				$this->style->value['height']	= max($h, $this->style->value['height']);
			}
			
			switch($this->style->value['rotate'])
			{
				case 90:
					$tmp = $over_h; $over_h = $over_w; $over_w = $tmp;
					$tmp = $h_reel; $h_reel = $w_reel; $w_reel = $tmp;
					unset($tmp);
					$w = $this->style->value['height'];
					$h = $this->style->value['width'];
					$t_x =-$h;
					$t_y = 0;
					break;
					
				case 180:
					$w = $this->style->value['width'];
					$h = $this->style->value['height'];
					$t_x = -$w;
					$t_y = -$h;
					break;
					
				case 270:
					$tmp = $over_h; $over_h = $over_w; $over_w = $tmp;
					$tmp = $h_reel; $h_reel = $w_reel; $w_reel = $tmp;
					unset($tmp);
					$w = $this->style->value['height'];
					$h = $this->style->value['width'];
					$t_x = 0;
					$t_y =-$w;
					break;
					
				default:
					$w = $this->style->value['width'];
					$h = $this->style->value['height'];
					$t_x = 0;
					$t_y = 0;
					break;
			}

			if (!$this->style->value['position'])
			{
				if (
					$w < ($this->pdf->getW() - $this->pdf->getlMargin()-$this->pdf->getrMargin()) &&
					$this->pdf->getX() + $w>=($this->pdf->getW() - $this->pdf->getrMargin())
					)
					$this->o_BR(array());
	
				if (
						($h < ($this->pdf->getH() - $this->pdf->gettMargin()-$this->pdf->getbMargin())) &&
						($this->pdf->getY() + $h>=($this->pdf->getH() - $this->pdf->getbMargin())) && 
						!$this->isInOverflow
					)
					$this->setNewPage();
				
				// en cas d'alignement => correction
				$old = $this->style->getOldValues();
				$parent_w = $old['width'] ? $old['width'] : $this->pdf->getW() - $this->pdf->getlMargin() - $this->pdf->getrMargin();
				
				if ($parent_w>$w)
				{				
					if ($align_object=='center')		$this->pdf->setX($this->pdf->getX() + ($parent_w-$w)*0.5);
					else if ($align_object=='right')	$this->pdf->setX($this->pdf->getX() + $parent_w-$w);
				}
				
				$this->style->setPosition();
			}
			else
			{
				// en cas d'alignement => correction
				$old = $this->style->getOldValues();
				$parent_w = $old['width'] ? $old['width'] : $this->pdf->getW() - $this->pdf->getlMargin() - $this->pdf->getrMargin();
				
				if ($parent_w>$w)
				{
					if ($align_object=='center')		$this->pdf->setX($this->pdf->getX() + ($parent_w-$w)*0.5);
					else if ($align_object=='right')	$this->pdf->setX($this->pdf->getX() + $parent_w-$w);
				}
				
				$this->style->setPosition();
				$this->saveMax();
				$this->maxX = 0;
				$this->maxY = 0;
				$this->maxH = 0;
				$this->maxE = 0;
			}		
			
			if ($this->style->value['rotate'])
			{
				$this->pdf->startTransform();
				$this->pdf->setRotation($this->style->value['rotate']);
				$this->pdf->setTranslate($t_x, $t_y);
			}
			
			// initialisation du style des bordures de la div
			$this->drawRectangle(
					$this->style->value['x'],
					$this->style->value['y'],
					$this->style->value['width'],
					$this->style->value['height'],
					$this->style->value['border'],
					$this->style->value['padding'],
					0,
					$this->style->value['background']
				);

			$marge = array();
			$marge['l'] = $this->style->value['border']['l']['width'] + $this->style->value['padding']['l']+0.03;
			$marge['r'] = $this->style->value['border']['r']['width'] + $this->style->value['padding']['r']+0.03;
			$marge['t'] = $this->style->value['border']['t']['width'] + $this->style->value['padding']['t']+0.03;
			$marge['b'] = $this->style->value['border']['b']['width'] + $this->style->value['padding']['b']+0.03;

			$this->style->value['width'] -= $marge['l']+$marge['r'];
			$this->style->value['height']-= $marge['t']+$marge['b'];
		
			// positionnement en fonction des alignements
			$x_corr = 0;
			$y_corr = 0;
			if (!$this->sub_part && !$this->isSubPart)
			{
				switch($this->style->value['text-align'])
				{
					case 'right':	$x_corr = ($this->style->value['width']-$w_reel); break;
					case 'center':	$x_corr = ($this->style->value['width']-$w_reel)*0.5; break;
				}
				if ($x_corr>0) $x_corr=0;
				switch($this->style->value['vertical-align'])
				{
					case 'bottom':	$y_corr = ($this->style->value['height']-$h_reel); break;
					case 'middle':	$y_corr = ($this->style->value['height']-$h_reel)*0.5; break;
				}
			}
			
			if ($overflow)
			{
				$over_w-= $marge['l']+$marge['r'];
				$over_h-= $marge['t']+$marge['b'];
				$this->pdf->clippingPathOpen(
					$this->style->value['x']+$marge['l'],
					$this->style->value['y']+$marge['t'],
					$this->style->value['width'],
					$this->style->value['height']
				);

				$this->style->value['x']+= $x_corr;
				// limitation des marges aux dimensions du contenu
				$mL = $this->style->value['x']+$marge['l'];
				$mR = $this->pdf->getW() - $mL - $over_w;
			}
			else
			{
				// limitation des marges aux dimensions de la div
				$mL = $this->style->value['x']+$marge['l'];
				$mR = $this->pdf->getW() - $mL - $this->style->value['width'];
			}
			
			$x = $this->style->value['x']+$marge['l'];
			$y = $this->style->value['y']+$marge['t']+$y_corr;
			$this->saveMargin($mL, 0, $mR);
			$this->pdf->setXY($x, $y);
			
			$this->setNewPositionForNewLine();
			
			return true;
		}
		protected function o_BLOCKQUOTE($param) { return $this->o_DIV($param, 'blockquote'); }
		protected function o_LEGEND($param) { return $this->o_DIV($param, 'legend'); }
		
		/**
		* balise : FIELDSET
		* mode : OUVERTURE
		* ecrite par Pavel Kochman
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_FIELDSET($param)
		{

			$this->style->save();
			$this->style->analyse('fieldset', $param);

			// get height of LEGEND element and make fieldset corrections
			for($temp_pos = $this->parse_pos + 1; $temp_pos<count($this->parsing->code); $temp_pos++)
			{
				$todo = $this->parsing->code[$temp_pos];
				if($todo['name'] == 'fieldset') break;
				if($todo['name'] == 'legend' && !$todo['close'])
				{
					$legend_open_pos = $temp_pos;

					$sub = null;
					$this->CreateSubHTML($sub);
					$sub->parsing->code = $this->parsing->getLevel($temp_pos - 1);

					// pour chaque element identifi� par le parsing
					$res = null;
					for($sub->parse_pos = 0; $sub->parse_pos<count($sub->parsing->code); $sub->parse_pos++)
					{
						$todo = $sub->parsing->code[$sub->parse_pos];
						$sub->loadAction($todo);
						
						if ($todo['name'] == 'legend' && $todo['close'])
							break;
					}

					$legendH = $sub->maxY;
					$this->destroySubHTML($sub);

					$move = $this->style->value['padding']['t'] + $this->style->value['border']['t']['width'] + 0.03;
					
					$param['moveTop'] = $legendH / 2;
					
					$this->parsing->code[$legend_open_pos]['param']['moveTop'] = - ($legendH / 2 + $move);
					$this->parsing->code[$legend_open_pos]['param']['moveLeft'] = 2 - $this->style->value['border']['l']['width'] - $this->style->value['padding']['l'];
					$this->parsing->code[$legend_open_pos]['param']['moveDown'] = $move;
					break;
				}
			}
			$this->style->load();
			
			return $this->o_DIV($param, 'fieldset');
		}
				
		/**
		* balise : DIV
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_DIV($param, $other='div')
		{
			if ($this->forOneLine) return false;
			
			if ($this->style->value['overflow']=='hidden')
			{
				$this->maxX = $this->style->value['old_maxX'];
				$this->maxY = $this->style->value['old_maxY'];
				$this->maxH = $this->style->value['old_maxH'];
				$this->isInOverflow = $this->style->value['old_overflow'];
				$this->pdf->clippingPathClose();
			}
				
			if ($this->style->value['rotate'])
				$this->pdf->stopTransform();
			
			$marge = array();
			$marge['l'] = $this->style->value['border']['l']['width'] + $this->style->value['padding']['l']+0.03;
			$marge['r'] = $this->style->value['border']['r']['width'] + $this->style->value['padding']['r']+0.03;
			$marge['t'] = $this->style->value['border']['t']['width'] + $this->style->value['padding']['t']+0.03;
			$marge['b'] = $this->style->value['border']['b']['width'] + $this->style->value['padding']['b']+0.03;
			
			$x = $this->style->value['x'];
			$y = $this->style->value['y'];
			$w = $this->style->value['width']+$marge['l']+$marge['r']+$this->style->value['margin']['r'];
			$h = $this->style->value['height']+$marge['t']+$marge['b']+$this->style->value['margin']['b'];
			
			switch($this->style->value['rotate'])
			{
				case 90:
					$t = $w; $w = $h; $h = $t;
					break;
					
				case 270:
					$t = $w; $w = $h; $h = $t;
					break;
				
				default:
					break;
			}


			if ($this->style->value['position']!='absolute')
			{
				// position
				$this->pdf->setXY($x+$w, $y); 	
				
				// position MAX
				$this->maxX = max($this->maxX, $x+$w);
				$this->maxY = max($this->maxY, $y+$h);
		 		$this->maxH = max($this->maxH, $h);
			}
			else
			{
				// position
				$this->pdf->setXY($this->style->value['xc'], $this->style->value['yc']); 	
				 	
				$this->loadMax();
			}
	 	
	 		$block = ($this->style->value['display']!='inline' && $this->style->value['position']!='absolute');
	 		
	 		$this->style->load();
			$this->style->FontSet();
			$this->loadMargin();
			
			if ($block) $this->o_BR(array());
			if ($this->DEBUG_actif) $this->DEBUG_add(strtoupper($other), false);
			
			return true;
		}
		protected function c_BLOCKQUOTE($param) { return $this->c_DIV($param, 'blockquote'); }
		protected function c_FIELDSET($param) { return $this->c_DIV($param, 'fieldset'); }
		protected function c_LEGEND($param) { return $this->c_DIV($param, 'legend'); }

		/**
		* balise : BARCODE
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_BARCODE($param)
		{
			// pour compatibilit� < 3.29
			$lst_barcode = array();
			$lst_barcode['UPC_A']	=	'UPCA';
			$lst_barcode['CODE39']	=	'C39';

			if (!isset($param['type']))		$param['type'] = 'C39';
			if (!isset($param['value']))	$param['value']	= 0;
			if (!isset($param['label']))	$param['label']	= 'label';
			if (!isset($param['style']['color'])) $param['style']['color'] = '#000000';
			
			if ($this->testIsDeprecated && (isset($param['bar_h']) || isset($param['bar_w'])))
				throw new HTML2PDF_exception(9, array('BARCODE', 'bar_h, bar_w'));
			
			$param['type'] = strtoupper($param['type']);
			if (isset($lst_barcode[$param['type']])) $param['type'] = $lst_barcode[$param['type']];
			
			$this->style->save();
			$this->style->analyse('barcode', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			$x = $this->pdf->getX();
			$y = $this->pdf->getY();
			$w = $this->style->value['width'];	if (!$w) $w = $this->style->ConvertToMM('50mm');
			$h = $this->style->value['height'];	if (!$h) $h = $this->style->ConvertToMM('10mm');
			$txt = ($param['label']!=='none' ? $this->style->value['font-size'] : false);
			$c = $this->style->value['color'];
			$infos = $this->pdf->myBarcode($param['value'], $param['type'], $x, $y, $w, $h, $txt, $c);
			
			// position maximale globale
			$this->maxX = max($this->maxX, $x+$infos[0]);
			$this->maxY = max($this->maxY, $y+$infos[1]);
 			$this->maxH = max($this->maxH, $infos[1]);
 			$this->maxE++;
 			
			$this->pdf->setXY($x+$infos[0], $y);
 			
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}

		/**
		* balise : BARCODE
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_BARCODE($param)
		{
			// completement inutile
			
			return true;
		}
		
		/**
		* balise : QRCODE
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_QRCODE($param)
		{
			if ($this->testIsDeprecated && (isset($param['size']) || isset($param['noborder'])))
				throw new HTML2PDF_exception(9, array('QRCODE', 'size, noborder'));
			
			if ($this->DEBUG_actif) $this->DEBUG_add('QRCODE', true);
			
			if (!isset($param['value']))						$param['value']	= '';
			if (!isset($param['ec']))							$param['ec']	= 'H';
			if (!isset($param['style']['color']))				$param['style']['color'] = '#000000';
			if (!isset($param['style']['background-color']))	$param['style']['background-color'] = '#FFFFFF';
			if (isset($param['style']['border']))
			{
				$borders = $param['style']['border']!='none';
				unset($param['style']['border']);
			}
			else
				$borders = true;
			
			if ($param['value']==='') return true;
			if (!in_array($param['ec'], array('L', 'M', 'Q', 'H'))) $param['ec'] = 'H';
			
			$this->style->save();
			$this->style->analyse('qrcode', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			$x = $this->pdf->getX();
			$y = $this->pdf->getY();
			$w = $this->style->value['width'];
			$h = $this->style->value['height'];
			$size = max($w, $h); if (!$size) $size = $this->style->ConvertToMM('50mm');
			
			$style = array(
				    'fgcolor' => $this->style->value['color'],
				    'bgcolor' => $this->style->value['background']['color'],
				);

			if ($borders)
			{
				$style['border'] = true;
				$style['padding'] = 'auto';
			}
			else
			{
				$style['border'] = false;
				$style['padding'] = 0;
			}
				
			if (!$this->sub_part && !$this->isSubPart)
			{
				$this->pdf->write2DBarcode($param['value'], 'QRCODE,'.$param['ec'], $x, $y, $size, $size, $style);
			}
			
			// position maximale globale
			$this->maxX = max($this->maxX, $x+$size);
			$this->maxY = max($this->maxY, $y+$size);
 			$this->maxH = max($this->maxH, $size);
 			
 			$this->pdf->setX($x+$size);
 			
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		
		/**
		* balise : QRCODE
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_QRCODE($param)
		{
			if ($this->DEBUG_actif) $this->DEBUG_add('QRCODE', false);
			// completement inutile
			return true;
		}
				
		/**
		* balise : BOOKMARK
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_BOOKMARK($param)
		{
			$titre = isset($param['title']) ? trim($param['title']) : '';
			$level = isset($param['level']) ? floor($param['level']) : 0;
			
			if ($level<0) $level = 0;
			if ($titre) $this->pdf->Bookmark($titre, $level, -1);
			
			return true;
		}
			
		/**
		* balise : BOOKMARK
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_BOOKMARK($param)
		{
			// completement inutile
			
			return true;
		}
		
		function getElementY($h)
		{
			if ($this->sub_part || $this->isSubPart || !$this->currentH || $this->currentH<$h)
				return 0;
				
			return ($this->currentH-$h)*0.8;
		}
		
		/**
		* balise : WRITE
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_WRITE($param)
		{
			$fill = ($this->style->value['background']['color']!==null && $this->style->value['background']['image']===null);
			if (in_array($this->style->value['id_balise'], array('fieldset', 'legend', 'div', 'table', 'tr', 'td', 'th')))
				$fill = false;
			
			// r�cup�ration du texte � �crire, et conversion
			$txt = $param['txt'];
			
			if ($this->isAfterFloat)
			{
				$txt = ltrim($txt);
				$this->isAfterFloat = false;
			}

			$txt = str_replace('[[page_cu]]',	$this->page,	$txt);
			
			if ($this->style->value['text-transform']!='none')
			{
				if ($this->style->value['text-transform']=='capitalize')
					$txt = ucwords($txt);
				else if ($this->style->value['text-transform']=='uppercase')
					$txt = strtoupper($txt);
				else if ($this->style->value['text-transform']=='lowercase')
					$txt = strtolower($txt);
			}
			
			// tailles du texte
			$h	= 1.08*$this->style->value['font-size'];
			$dh	= $h*$this->style->value['mini-decal'];
			$lh = $this->style->getLineHeight();
			
			// identification de l'alignement
			$align = 'L';
			if ($this->style->value['text-align']=='li_right')
			{
				$w = $this->style->value['width'];
				$align = 'R';
			}

			// pr� calcul de la taille de chaque mot et de la phrase complete
			$w = 0;
			$words = explode(' ', $txt);
			foreach($words as $k => $word)
			{
				$words[$k] = array($word, $this->pdf->GetStringWidth($word));
				$w+= $words[$k][1];
			}
			$space = $this->pdf->GetStringWidth(' ');
			$w+= $space*(count($words)-1);

			$curr_pos = 0;									// position dans le texte
			$curr_max = strlen($txt);						// taille maxi du texte
			$maxX = 0;										// plus grande largeur du texte apres retour � la ligne
			$x = $this->pdf->getX();						// position du texte
			$y = $this->pdf->getY();
			$dy = $this->getElementY($lh);
						
			list($left, $right) = $this->getMargins($y);	// marges autorisees
			$nb = 0;										// nbr de lignes d�coup�es
			
			// tant que ca ne rentre pas sur la ligne et qu'on a du texte => on d�coupe
			while($x+$w>$right && $x<$right && count($words))
			{
				// trouver une phrase qui rentre dans la largeur, en ajoutant les mots 1 � 1
				$i=0;
				$old = array('', 0);
				$str = $words[0];
				$add = false;
				while(($x+$str[1])<$right)
				{
					$i++;
					$add = true;
					
					array_shift($words);
					$old = $str;

					if (!count($words)) break;
					$str[0].= ' '.$words[0][0];
					$str[1]+= $space+$words[0][1];
				}
				$str = $old;
				
				// si rien de rentre, et que le premier mot ne rentre de toute facon pas dans une ligne, on le force...
				if ($i==0 && (($left+$words[0][1])>=$right))
				{
					$str = $words[0];
					array_shift($words);
					$i++;
					$add = true;
				}
				$curr_pos+= ($curr_pos ? 1 : 0)+strlen($str[0]);
				
				// ecriture du bout de phrase extrait et qui rentre
				$wc = ($align=='L' ? $str[1] : $this->style->value['width']);
				if ($right - $left<$wc) $wc = $right - $left;
/*
				if ($this->pdf->ws)
				{
					$oldSpace = $this->pdf->CurrentFont['cw'][' '];
					$this->pdf->CurrentFont['cw'][' ']*=(1.+$this->pdf->ws);
					$wc = $str[1];
					$this->pdf->CurrentFont['cw'][' '] = $oldSpace;
				}
*/
				if (strlen($str[0]))
				{
					$this->pdf->setXY($this->pdf->getX(), $y+$dh+$dy);
					$this->pdf->Cell($wc, $h, $str[0], 0, 0, $align, $fill, $this->inLink);
					$this->pdf->setXY($this->pdf->getX(), $y);
				}
				$this->maxH = max($this->maxH, $lh);
				
				// d�termination de la largeur max
				$maxX = max($maxX, $this->pdf->getX());

				// nouvelle position et nouvelle largeur pour la boucle
				$w-= $str[1];
				$y = $this->pdf->getY();
				$x = $this->pdf->getX();
				$dy = $this->getElementY($lh);
				
				// si il reste du text � afficher
				if (count($words))
				{
					if ($add) $w-= $space;
					if ($this->forOneLine)
					{
						$this->maxE+= $i+1;
						$this->maxX = max($this->maxX, $maxX);
						return null;
					}
					
					// retour � la ligne
					$this->o_BR(array('style' => ''), $curr_pos);
					
					$y = $this->pdf->getY();
					$x = $this->pdf->getX();
					$dy = $this->getElementY($lh);
					
					// si la prochaine ligne ne rentre pas dans la page => nouvelle page 
					if ($y + $h>=$this->pdf->getH() - $this->pdf->getbMargin())
					{
						if (!$this->isInOverflow && !$this->isInFooter)
						{
							$this->setNewPage(null, '', null, $curr_pos);
							$y = $this->pdf->getY();
							$x = $this->pdf->getX();
							$dy = $this->getElementY($lh);
						}
					}
				
					// ligne supl�mentaire. au bout de 1000 : trop long => erreur
					$nb++;
					if ($nb>1000)
					{
						$txt = ''; foreach($words as $k => $word) $txt.= ($k ? ' ' : '').$word[0];
						throw new HTML2PDF_exception(2, array($txt, $right-$left, $w));
					}

					list($left, $right) = $this->getMargins($y);	// marges autorisees
				}
			}
				
			// si il reste du text apres d�coupe, c'est qu'il rentre direct => on l'affiche
			if (count($words))
			{
				$txt = ''; foreach($words as $k => $word) $txt.= ($k ? ' ' : '').$word[0];
/*
				if ($this->pdf->ws)
				{
					$oldSpace = $this->pdf->CurrentFont['cw'][' '];
					$this->pdf->CurrentFont['cw'][' ']*=(1.+$this->pdf->ws);
					$w = $this->pdf->GetStringWidth($txt);
					$this->pdf->CurrentFont['cw'][' '] = $oldSpace;
				}
*/
				$this->pdf->setXY($this->pdf->getX(), $y+$dh+$dy);
				$this->pdf->Cell(($align=='L' ? $w : $this->style->value['width']), $h, $txt, 0, 0, $align, $fill, $this->inLink);
				$this->pdf->setXY($this->pdf->getX(), $y);	
				$this->maxH = max($this->maxH, $lh);
				$this->maxE+= count($words);
			}
			
			// d�termination des positions MAX
			$maxX = max($maxX, $this->pdf->getX());
			$maxY = $this->pdf->getY()+$h;
			
			// position maximale globale
			$this->maxX = max($this->maxX, $maxX);
			$this->maxY = max($this->maxY, $maxY);
			
			return true;
		}

		/**
		* tracer une image
		* 
		* @param	string	nom du fichier source
		* @return	null
		*/	
		protected function Image($src, $sub_li=false)
		{
			// est-ce que c'est une image ?
			$infos=@GetImageSize($src);

			if (count($infos)<2)
			{
				if ($this->testIsImage)
					throw new HTML2PDF_exception(6, $src);
				
				$src = null;
				$infos = array(16, 16);
			}
			
			// r�cup�ration des dimensions dans l'unit� du PDF
			$wi = $infos[0]/$this->pdf->getK();
			$hi = $infos[1]/$this->pdf->getK();
			
			// d�termination des dimensions d'affichage en fonction du style
			if ($this->style->value['width'] && $this->style->value['height'])
			{
				$w = $this->style->value['width'];
				$h = $this->style->value['height'];
			}
			else if ($this->style->value['width'])
			{
				$w = $this->style->value['width'];
				$h = $hi*$w/$wi;
				
			}
			else if ($this->style->value['height'])
			{
				$h = $this->style->value['height'];
				$w = $wi*$h/$hi;
			}
			else
			{
				$w = 72./96.*$wi;
				$h = 72./96.*$hi;					
			}
			
			// detection du float
			$float = $this->style->getFloat();
			if ($float && $this->maxH)
				if (!$this->o_BR(array()))
					return false;

			// position d'affichage
			$x = $this->pdf->getX();
			$y = $this->pdf->getY();
			
			// si l'image ne rentre pas dans la ligne => nouvelle ligne 
			if (!$float && ($x + $w>$this->pdf->getW() - $this->pdf->getrMargin()) && $this->maxH)
			{
				if ($this->forOneLine) return null;

				$hnl = max($this->maxH, $this->style->getLineHeight());
				$this->setNewLine($hnl);
				$x = $this->pdf->getX();
				$y = $this->pdf->getY();
			}
			
			// si l'image ne rentre pas dans la page => nouvelle page 
			if (
					($y + $h>$this->pdf->getH() - $this->pdf->getbMargin()) && 
					!$this->isInOverflow
				)
			{
				$this->setNewPage();
				$x = $this->pdf->getX();
				$y = $this->pdf->getY();
			}

			// correction pour l'affichage d'une puce image
			$hT = 0.80*$this->style->value['font-size'];
			if ($sub_li && $h<$hT)
			{
				$y+=($hT-$h);
			}

			$yc = $y-$this->style->value['margin']['t'];

			// d�termination de la position r�elle d'affichage en fonction du text-align du parent
			$old = $this->style->getOldValues();

			if ( $old['width'])
			{
				$parent_w = $old['width'];
				$parent_x = $x;
			}
			else
			{
 				$parent_w = $this->pdf->getW() - $this->pdf->getlMargin() - $this->pdf->getrMargin();
				$parent_x = $this->pdf->getlMargin();
			}
			
			if ($float)
			{
				list($lx, $rx) = $this->getMargins($yc);
				$parent_x = $lx;
				$parent_w = $rx-$lx;
			}

			if ($parent_w>$w && $float!='left')
			{
				if ($float=='right' || $this->style->value['text-align']=='li_right')	$x = $parent_x + $parent_w - $w-$this->style->value['margin']['r']-$this->style->value['margin']['l'];
			}
			
			// affichage de l'image, et positionnement � la suite
			if (!$this->sub_part && !$this->isSubPart)
			{
				if ($src) $this->pdf->Image($src, $x, $y, $w, $h, '', $this->inLink);
				else
				{
					$this->pdf->setFillColorArray(array(240, 220, 220));
					$this->pdf->Rect($x, $y, $w, $h, 'F');
				}
			}

			$x-= $this->style->value['margin']['l'];
			$y-= $this->style->value['margin']['t'];
			$w+= $this->style->value['margin']['l'] + $this->style->value['margin']['r'];
			$h+= $this->style->value['margin']['t'] + $this->style->value['margin']['b'];

			if ($float=='left')
			{
				$this->maxX = max($this->maxX, $x+$w);
				$this->maxY = max($this->maxY, $y+$h);

				$this->addMargins($float, $x, $y, $x+$w, $y+$h);

				list($lx, $rx) = $this->getMargins($yc);
				$this->pdf->setXY($lx, $yc);
	 		}
			else if ($float=='right')
			{
//				$this->maxX = max($this->maxX, $x+$w);
				$this->maxY = max($this->maxY, $y+$h);

				$this->addMargins($float, $x, $y, $x+$w, $y+$h);

				list($lx, $rx) = $this->getMargins($yc);
				$this->pdf->setXY($lx, $yc);
			}
			else
			{
				$this->pdf->setX($x+$w);
				$this->maxX = max($this->maxX, $x+$w);
				$this->maxY = max($this->maxY, $y+$h);
	 			$this->maxH = max($this->maxH, $h);
			}
			return true;
		}
		
		/**
		* Tracer un rectanble
		* 
		* @param	float	position X
		* @param	float	position Y
		* @param	float	Largeur
		* @param	float	Hauteur
		* @param	array	Tableau de style de d�finition des borders
		* @param	float	padding - marge int�rieur au rectangle => non utile mais on le passe en param�tre
		* @param	float	margin - marge exterieur au rectangle
		* @param	array	Tableau de style de d�finition du background
		* @return	null
		*/	
		protected function drawRectangle($x, $y, $w, $h, $border, $padding, $margin, $background)
		{
			if ($this->sub_part || $this->isSubPart) return false;
			if ($h===null) return false;
			
			$x+= $margin;
			$y+= $margin;
			$w-= $margin*2;
			$h-= $margin*2;
			
			// r�cup�ration des radius
			$out_TL = $border['radius']['tl'];
			$out_TR = $border['radius']['tr'];
			$out_BR = $border['radius']['br'];
			$out_BL = $border['radius']['bl'];
			
			// verification des coins en radius
			$out_TL = ($out_TL[0] && $out_TL[1]) ? $out_TL : null;
			$out_TR = ($out_TR[0] && $out_TR[1]) ? $out_TR : null;
			$out_BR = ($out_BR[0] && $out_BR[1]) ? $out_BR : null;
			$out_BL = ($out_BL[0] && $out_BL[1]) ? $out_BL : null;

			
			$in_TL = $out_TL;
			$in_TR = $out_TR;
			$in_BR = $out_BR;
			$in_BL = $out_BL;
			
			if (is_array($in_TL)) { $in_TL[0]-= $border['l']['width']; $in_TL[1]-= $border['t']['width']; }
			if (is_array($in_TR)) { $in_TR[0]-= $border['r']['width']; $in_TR[1]-= $border['t']['width']; }
			if (is_array($in_BR)) { $in_BR[0]-= $border['r']['width']; $in_BR[1]-= $border['b']['width']; }
			if (is_array($in_BL)) { $in_BL[0]-= $border['l']['width']; $in_BL[1]-= $border['b']['width']; }
			
			if ($in_TL[0]<=0 || $in_TL[1]<=0) $in_TL = null;
			if ($in_TR[0]<=0 || $in_TR[1]<=0) $in_TR = null;
			if ($in_BR[0]<=0 || $in_BR[1]<=0) $in_BR = null;
			if ($in_BL[0]<=0 || $in_BL[1]<=0) $in_BL = null;
					
			// traitement de la couleur de fond
			$STYLE = '';
			if ($background['color'])
			{
				$this->pdf->setFillColorArray($background['color']);
				$STYLE.= 'F';		
			}
			
			if ($STYLE)
			{
				$this->pdf->clippingPathOpen($x, $y, $w, $h, $out_TL,$out_TR, $out_BL, $out_BR);		
				$this->pdf->Rect($x, $y, $w, $h, $STYLE);
				$this->pdf->clippingPathClose();		
			}
			
			// traitement de l'image de fond
			if ($background['image'])
			{
				$i_name		= $background['image'];
				$i_position	= $background['position']!==null	? $background['position']	: array(0, 0);
				$i_repeat	= $background['repeat']!==null		? $background['repeat']		: array(true, true);
				
				// taile du fond (il faut retirer les borders
				$b_x = $x;
				$b_y = $y;
				$b_w = $w;
				$b_h = $h;
				
				if ($border['b']['width']) { $b_h-= $border['b']['width']; }
				if ($border['l']['width']) { $b_w-= $border['l']['width']; $b_x+= $border['l']['width']; }
				if ($border['t']['width']) { $b_h-= $border['t']['width']; $b_y+= $border['t']['width']; }
				if ($border['r']['width']) { $b_w-= $border['r']['width']; }

				// est-ce que c'est une image ?
				$i_infos=@GetImageSize($i_name);
	
				if (count($i_infos)<2)
				{
					if ($this->testIsImage)
						throw new HTML2PDF_exception(6, $i_name);
				}
				else
				{
					// r�cup�ration des dimensions dans l'unit� du PDF
					$i_width	= 72./96.*$i_infos[0]/$this->pdf->getK();
					$i_height	= 72./96.*$i_infos[1]/$this->pdf->getK();
					
					if ($i_repeat[0]) $i_position[0] = $b_x;
					else if(preg_match('/^([-]?[0-9\.]+)%/isU', $i_position[0], $match)) $i_position[0] = $b_x + $match[1]*($b_w-$i_width)/100;
					else $i_position[0] = $b_x+$i_position[0];
				
					if ($i_repeat[1]) $i_position[1] = $b_y;
					else if(preg_match('/^([-]?[0-9\.]+)%/isU', $i_position[1], $match)) $i_position[1] = $b_y + $match[1]*($b_h-$i_height)/100;
					else $i_position[1] = $b_y+$i_position[1];
					
					$i_x_min = $b_x;
					$i_x_max = $b_x+$b_w;
					$i_y_min = $b_y;
					$i_y_max = $b_y+$b_h;
					
					if (!$i_repeat[0] && !$i_repeat[1])
					{
						$i_x_min = 	$i_position[0]; $i_x_max = 	$i_position[0]+$i_width;
						$i_y_min = 	$i_position[1]; $i_y_max = 	$i_position[1]+$i_height;					
					}
					else if ($i_repeat[0] && !$i_repeat[1])
					{
						$i_y_min = 	$i_position[1]; $i_y_max = 	$i_position[1]+$i_height;					
					}
					elseif (!$i_repeat[0] && $i_repeat[1])
					{
						$i_x_min = 	$i_position[0]; $i_x_max = 	$i_position[0]+$i_width;
					}
					
					$this->pdf->clippingPathOpen($b_x, $b_y, $b_w, $b_h, $in_TL, $in_TR, $in_BL, $in_BR);		
					for ($i_y=$i_y_min; $i_y<$i_y_max; $i_y+=$i_height)
					{
						for ($i_x=$i_x_min; $i_x<$i_x_max; $i_x+=$i_width)
						{
							$c_x = null;
							$c_y = null;
							$c_w = $i_width;
							$c_h = $i_height;
							if ($i_y_max-$i_y<$i_height)
							{
								$c_x = $i_x;
								$c_y = $i_y;
								$c_h = $i_y_max-$i_y;
							}
							if ($i_x_max-$i_x<$i_width)
							{
								$c_x = $i_x;
								$c_y = $i_y;
								$c_w = $i_x_max-$i_x;
							}
	
							$this->pdf->Image($i_name, $i_x, $i_y, $i_width, $i_height, '', '');						
						}
					}
					$this->pdf->clippingPathClose();
				}
			}
			
			$x-= 0.01;
			$y-= 0.01;
			$w+= 0.02;
			$h+= 0.02;
			if ($border['l']['width']) $border['l']['width']+= 0.02;
			if ($border['t']['width']) $border['t']['width']+= 0.02;
			if ($border['r']['width']) $border['r']['width']+= 0.02;
			if ($border['b']['width']) $border['b']['width']+= 0.02;
			
			$Bl = ($border['l']['width'] && $border['l']['color'][0]!==null);
			$Bt = ($border['t']['width'] && $border['t']['color'][0]!==null);
			$Br = ($border['r']['width'] && $border['r']['color'][0]!==null);
			$Bb = ($border['b']['width'] && $border['b']['color'][0]!==null);
			
			if (is_array($out_BL) && ($Bb || $Bl))
			{
				if ($in_BL)
				{
					$courbe = array();
					$courbe[] = $x+$out_BL[0]; 				$courbe[] = $y+$h;
					$courbe[] = $x; 						$courbe[] = $y+$h-$out_BL[1];
					$courbe[] = $x+$out_BL[0];				$courbe[] = $y+$h-$border['b']['width'];
					$courbe[] = $x+$border['l']['width'];	$courbe[] = $y+$h-$out_BL[1];
					$courbe[] = $x+$out_BL[0];				$courbe[] = $y+$h-$out_BL[1];
				}
				else
				{
					$courbe = array();
					$courbe[] = $x+$out_BL[0]; 				$courbe[] = $y+$h;
					$courbe[] = $x; 						$courbe[] = $y+$h-$out_BL[1];
					$courbe[] = $x+$border['l']['width'];	$courbe[] = $y+$h-$border['b']['width'];
					$courbe[] = $x+$out_BL[0];				$courbe[] = $y+$h-$out_BL[1];
				}
				$this->drawCourbe($courbe, $border['l']['color']);
			}

		
			if (is_array($out_TL) && ($Bt || $Bl))
			{
				if ($in_TL)
				{
					$courbe = array();
					$courbe[] = $x; 						$courbe[] = $y+$out_TL[1];
					$courbe[] = $x+$out_TL[0]; 				$courbe[] = $y;
					$courbe[] = $x+$border['l']['width'];	$courbe[] = $y+$out_TL[1];
					$courbe[] = $x+$out_TL[0];				$courbe[] = $y+$border['t']['width'];
					$courbe[] = $x+$out_TL[0];				$courbe[] = $y+$out_TL[1];
				}
				else
				{
					$courbe = array();
					$courbe[] = $x; 						$courbe[] = $y+$out_TL[1];
					$courbe[] = $x+$out_TL[0]; 				$courbe[] = $y;
					$courbe[] = $x+$border['l']['width'];	$courbe[] = $y+$border['t']['width'];
					$courbe[] = $x+$out_TL[0];				$courbe[] = $y+$out_TL[1];
				}
				$this->drawCourbe($courbe, $border['t']['color']);
			}
			
			if (is_array($out_TR) && ($Bt || $Br))
			{
				if ($in_TR)
				{
					$courbe = array();
					$courbe[] = $x+$w-$out_TR[0]; 				$courbe[] = $y;
					$courbe[] = $x+$w; 							$courbe[] = $y+$out_TR[1];
					$courbe[] = $x+$w-$out_TR[0];				$courbe[] = $y+$border['t']['width'];
					$courbe[] = $x+$w-$border['r']['width'];	$courbe[] = $y+$out_TR[1];
					$courbe[] = $x+$w-$out_TR[0];				$courbe[] = $y+$out_TR[1];
				}
				else
				{
					$courbe = array();
					$courbe[] = $x+$w-$out_TR[0]; 				$courbe[] = $y;
					$courbe[] = $x+$w; 							$courbe[] = $y+$out_TR[1];
					$courbe[] = $x+$w-$border['r']['width'];	$courbe[] = $y+$border['t']['width'];
					$courbe[] = $x+$w-$out_TR[0];				$courbe[] = $y+$out_TR[1];
				}
				$this->drawCourbe($courbe, $border['r']['color']);
			}
			
			if (is_array($out_BR) && ($Bb || $Br))
			{
				if ($in_BR)
				{
					$courbe = array();
					$courbe[] = $x+$w; 							$courbe[] = $y+$h-$out_BR[1];
					$courbe[] = $x+$w-$out_BR[0]; 				$courbe[] = $y+$h;
					$courbe[] = $x+$w-$border['r']['width'];	$courbe[] = $y+$h-$out_BR[1];
					$courbe[] = $x+$w-$out_BR[0];				$courbe[] = $y+$h-$border['b']['width'];
					$courbe[] = $x+$w-$out_BR[0];				$courbe[] = $y+$h-$out_BR[1];
				}
				else
				{
					$courbe = array();
					$courbe[] = $x+$w; 							$courbe[] = $y+$h-$out_BR[1];
					$courbe[] = $x+$w-$out_BR[0]; 				$courbe[] = $y+$h;
					$courbe[] = $x+$w-$border['r']['width'];	$courbe[] = $y+$h-$border['b']['width'];
					$courbe[] = $x+$w-$out_BR[0];				$courbe[] = $y+$h-$out_BR[1];
				}
				$this->drawCourbe($courbe, $border['b']['color']);
			}
			
			if ($Bl)
			{
				$pt = array();
				$pt[] = $x;								$pt[] = $y+$h;
				$pt[] = $x;								$pt[] = $y+$h-$border['b']['width'];
				$pt[] = $x;								$pt[] = $y+$border['t']['width'];
				$pt[] = $x;								$pt[] = $y;
				$pt[] = $x+$border['l']['width'];		$pt[] = $y+$border['t']['width'];
				$pt[] = $x+$border['l']['width'];		$pt[] = $y+$h-$border['b']['width'];

				$bord = 3;			
				if (is_array($out_BL))
				{
					$bord-=1;
					$pt[3] -= $out_BL[1] - $border['b']['width'];
					if ($in_BL) $pt[11]-= $in_BL[1];
					unset($pt[0]);unset($pt[1]);
				}
				if (is_array($out_TL))
				{
					$bord-=2;
					$pt[5] += $out_TL[1]-$border['t']['width'];
					if ($in_TL) $pt[9] += $in_TL[1];
					unset($pt[6]);unset($pt[7]);
				}
				
				$pt = array_values($pt);
				$this->drawLine($pt, $border['l']['color'], $border['l']['type'], $border['l']['width'], $bord);
			}

			if ($Bt)
			{
				$pt = array();
				$pt[] = $x;								$pt[] = $y;
				$pt[] = $x+$border['l']['width'];		$pt[] = $y;
				$pt[] = $x+$w-$border['r']['width'];	$pt[] = $y;
				$pt[] = $x+$w;							$pt[] = $y;
				$pt[] = $x+$w-$border['r']['width'];	$pt[] = $y+$border['t']['width'];
				$pt[] = $x+$border['l']['width'];		$pt[] = $y+$border['t']['width'];

				$bord = 3;			
				if (is_array($out_TL))
				{
					$bord-=1;
					$pt[2] += $out_TL[0] - $border['l']['width'];
					if ($in_TL) $pt[10]+= $in_TL[0];
					unset($pt[0]);unset($pt[1]);
				}
				if (is_array($out_TR))
				{
					$bord-=2;
					$pt[4] -= $out_TR[0] - $border['r']['width'];
					if ($in_TR) $pt[8] -= $in_TR[0];
					unset($pt[6]);unset($pt[7]);
				}
				
				$pt = array_values($pt);
				$this->drawLine($pt, $border['t']['color'], $border['t']['type'], $border['t']['width'], $bord);
			}

			if ($Br)
			{
				$pt = array();
				$pt[] = $x+$w;								$pt[] = $y;
				$pt[] = $x+$w;								$pt[] = $y+$border['t']['width'];
				$pt[] = $x+$w;								$pt[] = $y+$h-$border['b']['width'];
				$pt[] = $x+$w;								$pt[] = $y+$h;
				$pt[] = $x+$w-$border['r']['width'];		$pt[] = $y+$h-$border['b']['width'];
				$pt[] = $x+$w-$border['r']['width'];		$pt[] = $y+$border['t']['width'];
				
				$bord = 3;			
				if (is_array($out_TR))
				{
					$bord-=1;
					$pt[3] += $out_TR[1] - $border['t']['width'];
					if ($in_TR) $pt[11]+= $in_TR[1];
					unset($pt[0]);unset($pt[1]);
				}
				if (is_array($out_BR))
				{
					$bord-=2;
					$pt[5] -= $out_BR[1] - $border['b']['width'];
					if ($in_BR) $pt[9] -= $in_BR[1];
					unset($pt[6]);unset($pt[7]);
				}
				
				$pt = array_values($pt);
				$this->drawLine($pt, $border['r']['color'], $border['r']['type'], $border['r']['width'], $bord);
			}
			
			if ($Bb)
			{
				$pt = array();
				$pt[] = $x+$w;							$pt[] = $y+$h;
				$pt[] = $x+$w-$border['r']['width'];	$pt[] = $y+$h;
				$pt[] = $x+$border['l']['width'];		$pt[] = $y+$h;
				$pt[] = $x;								$pt[] = $y+$h;
				$pt[] = $x+$border['l']['width'];		$pt[] = $y+$h-$border['b']['width'];
				$pt[] = $x+$w-$border['r']['width'];	$pt[] = $y+$h-$border['b']['width'];
				
				$bord = 3;			
				if (is_array($out_BL))
				{
					$bord-=2;
					$pt[4] += $out_BL[0] - $border['l']['width'];
					if ($in_BL) $pt[8] += $in_BL[0];
					unset($pt[6]);unset($pt[7]);
				}
				if (is_array($out_BR))
				{
					$bord-=1;
					$pt[2] -= $out_BR[0] - $border['r']['width'];
					if ($in_BR) $pt[10]-= $in_BR[0];
					unset($pt[0]);unset($pt[1]);
					
				}

				$pt = array_values($pt);
				$this->drawLine($pt, $border['b']['color'], $border['b']['type'], $border['b']['width'], $bord);
			}

			if ($background['color'])
			{
				$this->pdf->setFillColorArray($background['color']);
			}
		}
		
		protected function drawCourbe($pt, $color)
		{
			$this->pdf->setFillColorArray($color);
			
			if (count($pt)==10)
				$this->pdf->drawCourbe($pt[0], $pt[1], $pt[2], $pt[3], $pt[4], $pt[5], $pt[6], $pt[7], $pt[8], $pt[9]);
			else
				$this->pdf->drawCoin($pt[0], $pt[1], $pt[2], $pt[3], $pt[4], $pt[5], $pt[6], $pt[7]);
		}
		
		/**
		* Tracer une ligne epaisse d�fini par ses points avec des extreminites en biseau
		* 
		* @param	array	liste des points definissant le tour de la ligne
		* @param	float	couleur RVB
		* @param	string	type de ligne
		* @param	float	largeur de la ligne
		* @return	null
		*/	
		protected function drawLine($pt, $color, $type, $width, $bord=3)
		{
			$this->pdf->setFillColorArray($color);
			if ($type=='dashed' || $type=='dotted')
			{
				if ($bord==1)
				{
					$tmp = array(); $tmp[]=$pt[0]; $tmp[]=$pt[1]; $tmp[]=$pt[2]; $tmp[]=$pt[3]; $tmp[]=$pt[8]; $tmp[]=$pt[9];
					$this->pdf->Polygon($tmp, 'F');
					
					$tmp = array(); $tmp[]=$pt[2]; $tmp[]=$pt[3]; $tmp[]=$pt[4]; $tmp[]=$pt[5]; $tmp[]=$pt[6]; $tmp[]=$pt[7]; $tmp[]=$pt[8]; $tmp[]=$pt[9];
					$pt = $tmp;
				}
				else if ($bord==2)
				{
					$tmp = array(); $tmp[]=$pt[2]; $tmp[]=$pt[3]; $tmp[]=$pt[4]; $tmp[]=$pt[5]; $tmp[]=$pt[6]; $tmp[]=$pt[7];
					$this->pdf->Polygon($tmp, 'F');
					
					$tmp = array(); $tmp[]=$pt[0]; $tmp[]=$pt[1]; $tmp[]=$pt[2]; $tmp[]=$pt[3]; $tmp[]=$pt[6]; $tmp[]=$pt[7]; $tmp[]=$pt[8]; $tmp[]=$pt[9];
					$pt = $tmp;					
				}
				else if ($bord==3)
				{
					$tmp = array(); $tmp[]=$pt[0]; $tmp[]=$pt[1]; $tmp[]=$pt[2]; $tmp[]=$pt[3]; $tmp[]=$pt[10]; $tmp[]=$pt[11];
					$this->pdf->Polygon($tmp, 'F');
	
					$tmp = array(); $tmp[]=$pt[4]; $tmp[]=$pt[5]; $tmp[]=$pt[6]; $tmp[]=$pt[7]; $tmp[]=$pt[8]; $tmp[]=$pt[9];
					$this->pdf->Polygon($tmp, 'F');
					
					$tmp = array(); $tmp[]=$pt[2]; $tmp[]=$pt[3]; $tmp[]=$pt[4]; $tmp[]=$pt[5]; $tmp[]=$pt[8]; $tmp[]=$pt[9]; $tmp[]=$pt[10]; $tmp[]=$pt[11];
					$pt = $tmp;
				}
				
				if ($pt[2]==$pt[0])
				{
					$l = abs(($pt[3]-$pt[1])*0.5);
					$px = 0;
					$py = $width;
					$x1 = $pt[0]; $y1 = ($pt[3]+$pt[1])*0.5;
					$x2 = $pt[6]; $y2 = ($pt[7]+$pt[5])*0.5;
				}
				else
				{
					$l = abs(($pt[2]-$pt[0])*0.5);
					$px = $width;
					$py = 0;					
					$x1 = ($pt[2]+$pt[0])*0.5; $y1 = $pt[1];
					$x2 = ($pt[6]+$pt[4])*0.5; $y2 = $pt[7];
				}
				if ($type=='dashed')
				{
					$px = $px*3.;
					$py = $py*3.;
				}
				$mode = ($l/($px+$py)<.5);
				
				for($i=0; $l-($px+$py)*($i-0.5)>0; $i++)
				{
					if (($i%2)==$mode)
					{
						$j = $i-0.5;
						$lx1 = $px*($j);	if ($lx1<-$l)	$lx1 =-$l;
						$ly1 = $py*($j);	if ($ly1<-$l)	$ly1 =-$l;
						$lx2 = $px*($j+1);	if ($lx2>$l)	$lx2 = $l;
						$ly2 = $py*($j+1);	if ($ly2>$l)	$ly2 = $l;
						
						$tmp = array();
						$tmp[] = $x1+$lx1;	$tmp[] = $y1+$ly1;	
						$tmp[] = $x1+$lx2; 	$tmp[] = $y1+$ly2;	
						$tmp[] = $x2+$lx2; 	$tmp[] = $y2+$ly2;	
						$tmp[] = $x2+$lx1;	$tmp[] = $y2+$ly1;
						$this->pdf->Polygon($tmp, 'F');	

						if ($j>0)
						{
							$tmp = array();
							$tmp[] = $x1-$lx1;	$tmp[] = $y1-$ly1;	
							$tmp[] = $x1-$lx2; 	$tmp[] = $y1-$ly2;	
							$tmp[] = $x2-$lx2; 	$tmp[] = $y2-$ly2;	
							$tmp[] = $x2-$lx1;	$tmp[] = $y2-$ly1;
							$this->pdf->Polygon($tmp, 'F');	
						}
					}
				}
			}
			else if ($type=='double')
			{
				$pt1 = $pt;
				$pt2 = $pt;
				
				if (count($pt)==12)
				{
					$pt1[0] = ($pt[0]-$pt[10])*0.33 + $pt[10];
					$pt1[1] = ($pt[1]-$pt[11])*0.33 + $pt[11];
					$pt1[2] = ($pt[2]-$pt[10])*0.33 + $pt[10];
					$pt1[3] = ($pt[3]-$pt[11])*0.33 + $pt[11];
					$pt1[4] = ($pt[4]-$pt[8])*0.33 + $pt[8];
					$pt1[5] = ($pt[5]-$pt[9])*0.33 + $pt[9];
					$pt1[6] = ($pt[6]-$pt[8])*0.33 + $pt[8];
					$pt1[7] = ($pt[7]-$pt[9])*0.33 + $pt[9];
					$pt2[10]= ($pt[10]-$pt[0])*0.33 + $pt[0];
					$pt2[11]= ($pt[11]-$pt[1])*0.33 + $pt[1];
					$pt2[2] = ($pt[2] -$pt[0])*0.33 + $pt[0];
					$pt2[3] = ($pt[3] -$pt[1])*0.33 + $pt[1];
					$pt2[4] = ($pt[4] -$pt[6])*0.33 + $pt[6];
					$pt2[5] = ($pt[5] -$pt[7])*0.33 + $pt[7];
					$pt2[8] = ($pt[8] -$pt[6])*0.33 + $pt[6];
					$pt2[9] = ($pt[9] -$pt[7])*0.33 + $pt[7];
				}
				else
				{
					$pt1[0] = ($pt[0]-$pt[6])*0.33 + $pt[6];
					$pt1[1] = ($pt[1]-$pt[7])*0.33 + $pt[7];
					$pt1[2] = ($pt[2]-$pt[4])*0.33 + $pt[4];
					$pt1[3] = ($pt[3]-$pt[5])*0.33 + $pt[5];
					$pt2[6] = ($pt[6]-$pt[0])*0.33 + $pt[0];
					$pt2[7] = ($pt[7]-$pt[1])*0.33 + $pt[1];
					$pt2[4] = ($pt[4]-$pt[2])*0.33 + $pt[2];
					$pt2[5] = ($pt[5]-$pt[3])*0.33 + $pt[3];
				}
				$this->pdf->Polygon($pt1, 'F');
				$this->pdf->Polygon($pt2, 'F');
			}
			else if ($type=='solid')
			{
				$this->pdf->Polygon($pt, 'F');
			}
		}
	
		/**
		* balise : BR
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @param	integer	position reelle courante si saut de ligne pendant l'ecriture d'un texte 
		* @return	null
		*/	
		protected function o_BR($param, $curr = null)
		{
			if ($this->forOneLine) return false;
			
			$h = max($this->maxH, $this->style->getLineHeight());

			// si la ligne est vide, la position maximale n'a pas �t� mise � jour => on la met � jour
			if ($this->maxH==0) $this->maxY = max($this->maxY, $this->pdf->getY()+$h);
			
			$this->makeBR($h, $curr);
			
			$this->maxH = 0;
			
			return true;
		}
		
		protected function makeBR($h, $curr = null)
		{
			// si le saut de ligne rentre => on le prend en compte, sinon nouvelle page
			if ($h)
			{
				if (($this->pdf->getY()+$h<$this->pdf->getH() - $this->pdf->getbMargin()) || $this->isInOverflow || $this->isInFooter)
					$this->setNewLine($h, $curr);
				else
					$this->setNewPage(null, '', null, $curr);
			}
			else
			{
				$this->setNewPositionForNewLine($curr);	
			}
			
			$this->maxH = 0;
		}
		
		/**
		* balise : HR
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_HR($param)
		{
			if ($this->forOneLine) return false;
			$old_align = $this->style->value['text-align'];
			$this->style->value['text-align'] = 'left';

			if ($this->maxH) $this->o_BR($param);
			
			$f_size = $this->style->value['font-size'];
			$this->style->value['font-size']=$f_size*0.5; $this->o_BR($param);
			$this->style->value['font-size']=0;
			
			$param['style']['width'] = '100%';
			
			$this->style->save();
			$this->style->value['height']=$this->style->ConvertToMM('1mm');

			$this->style->analyse('hr', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			$h = $this->style->value['height'];
			if ($h)		$h-= $this->style->value['border']['t']['width']+$this->style->value['border']['b']['width'];
			if ($h<=0)	$h = $this->style->value['border']['t']['width']+$this->style->value['border']['b']['width'];

			$this->drawRectangle($this->pdf->getX(), $this->pdf->getY(), $this->style->value['width'], $h, $this->style->value['border'], 0, 0, $this->style->value['background']);
			$this->maxH = $h;

			$this->style->load();
			$this->style->FontSet();
			
			$this->o_BR($param);

			$this->style->value['font-size']=$f_size*0.5; $this->o_BR($param);
			$this->style->value['font-size']=$f_size;

			$this->style->value['text-align'] = $old_align;
			$this->setNewPositionForNewLine();
						
			return true;
		}

		/**
		* balise : B
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_B($param, $other = 'b')
		{
			$this->style->save();
			$this->style->value['font-bold'] = true;
			$this->style->analyse($other, $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			return true;
		}
		protected function o_STRONG($param) { return $this->o_B($param, 'strong'); }
				
		/**
		* balise : B
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_B($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		protected function c_STRONG($param) { return $this->c_B($param); }
		
		/**
		* balise : I
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_I($param, $other = 'i')
		{
			$this->style->save();
			$this->style->value['font-italic'] = true;
			$this->style->analyse($other, $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			return true;
		}	
		protected function o_ADDRESS($param)	{ return $this->o_I($param, 'address');	}
		protected function o_CITE($param)		{ return $this->o_I($param, 'cite');		}
		protected function o_EM($param)		{ return $this->o_I($param, 'em');			}
		protected function o_SAMP($param)		{ return $this->o_I($param, 'samp');		}

		/**
		* balise : I
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_I($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}	
		protected function c_ADDRESS($param)	{ return $this->c_I($param); }
		protected function c_CITE($param)		{ return $this->c_I($param); }
		protected function c_EM($param) 		{ return $this->c_I($param); }
		protected function c_SAMP($param)		{ return $this->c_I($param); }

		/**
		* balise : S
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_S($param, $other = 's')
		{
			$this->style->save();
			$this->style->value['font-linethrough'] = true;
			$this->style->analyse($other, $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			return true;
		}	
		protected function o_DEL($param) { return $this->o_S($param, 'del'); }
		
		/**
		* balise : S
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_S($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		protected function c_DEL($param) { return $this->c_S($param); }
		
		/**
		* balise : U
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_U($param, $other='u')
		{
			$this->style->save();
			$this->style->value['font-underline'] = true;
			$this->style->analyse($other, $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			return true;
		}	
		protected function o_INS($param) { return $this->o_U($param, 'ins'); }
		
		/**
		* balise : U
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_U($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		protected function c_INS($param) { return $this->c_U($param); }
		
		/**
		* balise : A
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_A($param)
		{
			$this->inLink = str_replace('&amp;', '&', isset($param['href']) ? $param['href'] : '');
			
			if (isset($param['name']))
			{
				$nom = 	$param['name'];
				if (!isset($this->lstAncre[$nom])) $this->lstAncre[$nom] = array($this->pdf->AddLink(), false);
				
				if (!$this->lstAncre[$nom][1])
				{
					$this->lstAncre[$nom][1] = true;
					$this->pdf->SetLink($this->lstAncre[$nom][0], -1, -1);
				}
			}
			
			if (preg_match('/^#([^#]+)$/isU', $this->inLink, $match))
			{
				$nom = $match[1];
				if (!isset($this->lstAncre[$nom])) $this->lstAncre[$nom] = array($this->pdf->AddLink(), false);
				
				$this->inLink = $this->lstAncre[$nom][0];
			}
			
			$this->style->save();
			$this->style->value['font-underline'] = true;
			$this->style->value['color'] = array(20, 20, 250);
			$this->style->analyse('a', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			return true;		
		}

		/**
		* balise : A
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_A($param)
		{
			$this->inLink	= '';
			$this->style->load();
			$this->style->FontSet();			
			
			return true;
		}

		/**
		* balise : H1
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_H1($param, $other = 'h1')
		{
			if ($this->forOneLine) return false;
			
			if ($this->maxH) $this->o_BR(array());
			$this->style->save();
			$this->style->value['font-bold'] = true;
			
			$size = array('h1' => '28px', 'h2' => '24px', 'h3' => '20px', 'h4' => '16px', 'h5' => '12px', 'h6' => '9px');
			$this->style->value['margin']['l'] = 0;
			$this->style->value['margin']['r'] = 0;
			$this->style->value['margin']['t'] = $this->style->ConvertToMM('16px');
			$this->style->value['margin']['b'] = $this->style->ConvertToMM('16px');
			$this->style->value['font-size'] = $this->style->ConvertToMM($size[$other]);

			$this->style->analyse($other, $param);
			$this->style->setPosition();
			$this->style->FontSet();
			$this->setNewPositionForNewLine();
			
			return true;
		}
		protected function o_H2($param)	{ return $this->o_H1($param, 'h2'); }
		protected function o_H3($param)	{ return $this->o_H1($param, 'h3'); }
		protected function o_H4($param)	{ return $this->o_H1($param, 'h4'); }
		protected function o_H5($param)	{ return $this->o_H1($param, 'h5'); }
		protected function o_H6($param)	{ return $this->o_H1($param, 'h6'); }
		
		
		/**
		* balise : H1
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_H1($param)
		{
			if ($this->forOneLine) return false;

			// hauteur du H1
			$this->maxH+= $this->style->value['margin']['b'];
			$h = max($this->maxH, $this->style->getLineHeight());
			
			$this->style->load();
			$this->style->FontSet();
			
			// saut de ligne et initialisation de la hauteur
			$this->makeBR($h);
			$this->maxH = 0;
			
			return true;
		}
		protected function c_H2($param)	{ return $this->c_H1($param); }
		protected function c_H3($param)	{ return $this->c_H1($param); }
		protected function c_H4($param)	{ return $this->c_H1($param); }
		protected function c_H5($param)	{ return $this->c_H1($param); }
		protected function c_H6($param)	{ return $this->c_H1($param); }
		
		/**
		* balise : SPAN
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_SPAN($param, $other = 'span')
		{
			$this->style->save();
			$this->style->analyse($other, $param);
			$this->style->setPosition();
			$this->style->FontSet();		
			
			return true;
		}	
		protected function o_FONT($param)		{ return $this->o_SPAN($param, 'font');	}
 		protected function o_LABEL($param)	{ return $this->o_SPAN($param, 'label');}
		
		/**
		* balise : SPAN
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_SPAN($param)
		{
			$this->style->restorePosition();
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		protected function c_FONT($param)		{ return $this->c_SPAN($param); }
		protected function c_LABEL($param)	{ return $this->c_SPAN($param); }
		

		/**
		* balise : P
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_P($param)
		{
			if ($this->forOneLine) return false;

			if (!in_array($this->previousCall, array('c_P', 'c_UL')))
			{
				if ($this->maxH) $this->o_BR(array());
			}
			
			$this->style->save();
			$this->style->analyse('p', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			 // annule les effets du setposition
			$this->pdf->setXY($this->pdf->getX()-$this->style->value['margin']['l'], $this->pdf->getY()-$this->style->value['margin']['t']);
			
			list($mL, $mR) = $this->getMargins($this->pdf->getY());
			$mR = $this->pdf->getW()-$mR;
			$mL+= $this->style->value['margin']['l']+$this->style->value['padding']['l'];
			$mR+= $this->style->value['margin']['r']+$this->style->value['padding']['r'];
			$this->saveMargin($mL,0,$mR);
			
			if ($this->style->value['text-indent']>0)
			{
				$y = $this->pdf->getY()+$this->style->value['margin']['t']+$this->style->value['padding']['t'];
				$this->pageMarges[floor($y*100)] = array($mL+$this->style->value['text-indent'], $this->pdf->getW()-$mR);
				$y+= $this->style->getLineHeight()*0.1;
				$this->pageMarges[floor($y*100)] = array($mL, $this->pdf->getW()-$mR);
			}
			$this->makeBR($this->style->value['margin']['t']+$this->style->value['padding']['t']);
			return true;
		}
		
		/**
		* balise : P
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_P($param)
		{
			if ($this->forOneLine) return false;

			if ($this->maxH) $this->o_BR(array());
			$this->loadMargin();
			$h = $this->style->value['margin']['b']+$this->style->value['padding']['b'];
			
			$this->style->load();
			$this->style->FontSet();
			$this->makeBR($h); 
			
			return true;
		}
		
		/**
		* balise : PRE
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_PRE($param, $other = 'pre')
		{
			if ($other=='pre' && $this->maxH) $this->o_BR(array());
			
			$this->style->save();
			$this->style->value['font-family']	= 'courier';
			$this->style->analyse($other, $param);
			$this->style->setPosition();
			$this->style->FontSet();

			if ($other=='pre') return $this->o_DIV($param, $other);
			
			return true;
		}
		protected function o_CODE($param) { return $this->o_PRE($param, 'code'); }
		
		/**
		* balise : PRE
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_PRE($param, $other = 'pre')
		{
			if ($other=='pre')
			{
				if ($this->forOneLine) return false;

				$this->c_DIV($param);
				$this->o_BR(array());
			}
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		protected function c_CODE($param) { return $this->c_PRE($param, 'code'); }
				
		/**
		* balise : BIG
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_BIG($param)
		{
			$this->style->save();
			$this->style->value['mini-decal']-= $this->style->value['mini-size']*0.12;
			$this->style->value['mini-size'] *= 1.2;
			$this->style->analyse('big', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			return true;
		}

		/**
		* balise : BIG
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_BIG($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}

		/**
		* balise : SMALL
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_SMALL($param)
		{
			$this->style->save();
			$this->style->value['mini-decal']+= $this->style->value['mini-size']*0.05;
			$this->style->value['mini-size'] *= 0.82;
			$this->style->analyse('small', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			return true;
		}
		 
		/**
		* balise : SMALL
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_SMALL($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}


		/**
		* balise : SUP
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_SUP($param)
		{
			$this->style->save();
			$this->style->value['mini-decal']-= $this->style->value['mini-size']*0.15;
			$this->style->value['mini-size'] *= 0.75;
			$this->style->analyse('sup', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			return true;
		}
		 
		/**
		* balise : SUP
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_SUP($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}

		/**
		* balise : SUB
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_SUB($param)
		{
			$this->style->save();
			$this->style->value['mini-decal']+= $this->style->value['mini-size']*0.15;
			$this->style->value['mini-size'] *= 0.75;
			$this->style->analyse('sub', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			return true;
		}
		 
		/**
		* balise : SUB
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_SUB($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}

		/**
		* balise : UL
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_UL($param, $other = 'ul')
		{
			if ($this->forOneLine) return false;

			if (!in_array($this->previousCall, array('c_P', 'c_UL')))
			{
				if ($this->maxH) $this->o_BR(array());
				if (!count($this->defLIST)) $this->o_BR(array());
			}
			
			if (!isset($param['style']['width'])) $param['allwidth'] = true;
			$param['cellspacing'] = 0;

			// une liste est trait�e comme un tableau
			$this->o_TABLE($param, $other);

			// ajouter un niveau de liste
			$this->listeAddLevel($other, $this->style->value['list-style-type'], $this->style->value['list-style-image']);
			
			return true;
		}
		protected function o_OL($param) { return $this->o_UL($param, 'ol'); }	
		
		/**
		* balise : UL
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/
		protected function c_UL($param)
		{
			if ($this->forOneLine) return false;

			// fin du tableau
			$this->c_TABLE($param);
			
			// enlever un niveau de liste
			$this->listeDelLevel();

			if (!$this->sub_part)
			{
				if (!count($this->defLIST)) $this->o_BR(array());
			}
			
			return true;
		}
		protected function c_OL($param) { return $this->c_UL($param); }

		/**
		* balise : LI
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/
		protected function o_LI($param)
		{
			if ($this->forOneLine) return false;

			// ajouter une puce au niveau actuel
			$this->listeAddLi();

			if (!isset($param['style']['width'])) $param['style']['width'] = '100%';

			// preparation du style de la puce
			$paramPUCE = $param;
			
			$inf = $this->listeGetLi();
			if ($inf[0])
			{
				$paramPUCE['style']['font-family']		= $inf[0];
				$paramPUCE['style']['text-align']		= 'li_right';
				$paramPUCE['style']['vertical-align']	= 'top';
				$paramPUCE['style']['width']			= $this->listeGetWidth();
				$paramPUCE['style']['padding-right']	= $this->listeGetPadding();
				$paramPUCE['txt'] = $inf[2];
			}
			else
			{
				$paramPUCE['style']['text-align']		= 'li_right';
				$paramPUCE['style']['vertical-align']	= 'top';
				$paramPUCE['style']['width']			= $this->listeGetWidth();
				$paramPUCE['style']['padding-right']	= $this->listeGetPadding();
				$paramPUCE['src'] = $inf[2];
				$paramPUCE['sub_li'] = true;
			}
			
			// nouvelle ligne
			$this->o_TR($param, 'li');

			$this->style->save();
			
			if ($inf[1]) // small
			{
				$this->style->value['mini-decal']+= $this->style->value['mini-size']*0.045;
				$this->style->value['mini-size'] *= 0.75;
			}
			
			// si on est dans un sub_html => preparation, sinon affichage classique
			if ($this->sub_part)
			{
				// TD pour la puce
				$tmp_pos = $this->temp_pos;
				$tmp_lst1 = $this->parsing->code[$tmp_pos+1];
				$tmp_lst2 = $this->parsing->code[$tmp_pos+2];
				$this->parsing->code[$tmp_pos+1] = array();
				$this->parsing->code[$tmp_pos+1]['name']	= (isset($paramPUCE['src'])) ? 'img' : 'write';
				$this->parsing->code[$tmp_pos+1]['param']	= $paramPUCE; unset($this->parsing->code[$tmp_pos+1]['param']['style']['width']);
				$this->parsing->code[$tmp_pos+1]['close']	= 0;
				$this->parsing->code[$tmp_pos+2] = array();
				$this->parsing->code[$tmp_pos+2]['name']	= 'li';
				$this->parsing->code[$tmp_pos+2]['param']	= $paramPUCE;
				$this->parsing->code[$tmp_pos+2]['close']	= 1;
				$this->o_TD($paramPUCE, 'li_sub');
				$this->c_TD($param);
				$this->temp_pos = $tmp_pos;
				$this->parsing->code[$tmp_pos+1] = $tmp_lst1;
				$this->parsing->code[$tmp_pos+2] = $tmp_lst2;
			}
			else
			{
				// TD pour la puce
				$this->o_TD($paramPUCE, 'li_sub');
				unset($paramPUCE['style']['width']);
				if (isset($paramPUCE['src']))	$this->o_IMG($paramPUCE);
				else							$this->o_WRITE($paramPUCE);
				$this->c_TD($paramPUCE);
			}
			$this->style->load();

				
			// td pour le contenu
			$this->o_TD($param, 'li');
			
			return true;
		}

		/**
		* balise : LI
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/
		protected function c_LI($param)
		{
			if ($this->forOneLine) return false;

			// fin du contenu
			$this->c_TD($param);
			
			// fin de la ligne
			$this->c_TR($param);
			
			return true;
		}
		
		/**
		* balise : TBODY
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_TBODY($param)
		{
			if ($this->forOneLine) return false;

			$this->style->save();
			$this->style->analyse('tbody', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			return true;
		}	

		/**
		* balise : TBODY
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_TBODY($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}

		/**
		* balise : THEAD
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_THEAD($param)
		{
			if ($this->forOneLine) return false;
			
			$this->style->save();
			$this->style->analyse('thead', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			// si on est en mode sub_html : sauvegarde du num�ro du TR 
			if ($this->sub_part)
			{
				HTML2PDF::$TABLES[$param['num']]['thead']['tr'][0] = HTML2PDF::$TABLES[$param['num']]['tr_curr'];
				HTML2PDF::$TABLES[$param['num']]['thead']['code'] = array(); 
				for($pos=$this->temp_pos; $pos<count($this->parsing->code); $pos++)
				{
					$todo = $this->parsing->code[$pos];
					if (strtolower($todo['name'])=='thead') $todo['name'] = 'thead_sub';
					HTML2PDF::$TABLES[$param['num']]['thead']['code'][] = $todo;
					if (strtolower($todo['name'])=='thead_sub' && $todo['close']) break;
				}
			}
			else
			{
				$level = $this->parsing->getLevel($this->parse_pos);
				$this->parse_pos+= count($level);
				HTML2PDF::$TABLES[$param['num']]['tr_curr']+= count(HTML2PDF::$TABLES[$param['num']]['thead']['tr']);
			}
			
			return true;
		}	

		/**
		* balise : THEAD
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_THEAD($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();
			$this->style->FontSet();

			// si on est en mode sub_html : sauvegarde du num�ro du TR 
			if ($this->sub_part)
			{
				$min = HTML2PDF::$TABLES[$param['num']]['thead']['tr'][0];
				$max = HTML2PDF::$TABLES[$param['num']]['tr_curr']-1;				
				HTML2PDF::$TABLES[$param['num']]['thead']['tr'] = range($min, $max);
			}
			
			return true;
		}

		/**
		* balise : TFOOT
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_TFOOT($param)
		{
			if ($this->forOneLine) return false;

			$this->style->save();
			$this->style->analyse('tfoot', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			// si on est en mode sub_html : sauvegarde du num�ro du TR 
			if ($this->sub_part)
			{
				HTML2PDF::$TABLES[$param['num']]['tfoot']['tr'][0] = HTML2PDF::$TABLES[$param['num']]['tr_curr'];
				HTML2PDF::$TABLES[$param['num']]['tfoot']['code'] = array(); 
				for($pos=$this->temp_pos; $pos<count($this->parsing->code); $pos++)
				{
					$todo = $this->parsing->code[$pos];
					if (strtolower($todo['name'])=='tfoot') $todo['name'] = 'tfoot_sub';
					HTML2PDF::$TABLES[$param['num']]['tfoot']['code'][] = $todo;
					if (strtolower($todo['name'])=='tfoot_sub' && $todo['close']) break;
				}
			}
			else
			{
				$level = $this->parsing->getLevel($this->parse_pos);
				$this->parse_pos+= count($level);
				HTML2PDF::$TABLES[$param['num']]['tr_curr']+= count(HTML2PDF::$TABLES[$param['num']]['tfoot']['tr']);
			}
			
			return true;
		}	

		/**
		* balise : TFOOT
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_TFOOT($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();
			$this->style->FontSet();
			
			// si on est en mode sub_html : sauvegarde du num�ro du TR 
			if ($this->sub_part)
			{
				$min = HTML2PDF::$TABLES[$param['num']]['tfoot']['tr'][0];
				$max = HTML2PDF::$TABLES[$param['num']]['tr_curr']-1;				
				HTML2PDF::$TABLES[$param['num']]['tfoot']['tr'] = range($min, $max);
			}
			
			return true;
		}

		/**
		* balise : THEAD_SUB
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_THEAD_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->style->save();
			$this->style->analyse('thead', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			return true;
		}
		
		/**
		* balise : THEAD_SUB
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_THEAD_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}

		/**
		* balise : TFOOT_SUB
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_TFOOT_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->style->save();
			$this->style->analyse('tfoot', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			return true;
		}
		
		/**
		* balise : TFOOT_SUB
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_TFOOT_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
				
		/**
		* balise : FORM
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_FORM($param)
		{
			$this->style->save();
			$this->style->analyse('form', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			$this->pdf->setFormDefaultProp(array(
					'lineWidth'=>1,
					'borderStyle'=>'solid',
					'fillColor'=>array(220, 220, 255),
					'strokeColor'=>array(128, 128, 200)
			));

			$this->isInForm = isset($param['action']) ? $param['action'] : '';
			
			return true;
		}	

		/**
		* balise : FORM
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_FORM($param)
		{
			$this->isInForm = false;
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		
		/**
		* balise : TABLE
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_TABLE($param, $other = 'table')
		{
			if ($this->maxH)
			{
				if ($this->forOneLine) return false;
				$this->o_BR(array());
			}
			
			if ($this->forOneLine)
			{
				$this->maxE++;
				$this->maxX = $this->pdf->getW() - $this->pdf->getlMargin() - $this->pdf->getrMargin();
				return false;		
			}
			
			$this->maxH = 0;

			$align_object = isset($param['align']) ? strtolower($param['align']) : 'left';
			if (isset($param['align'])) unset($param['align']);
			if (!in_array($align_object, array('left', 'center', 'right'))) $align_object = 'left';
			
			// lecture et initialisation du style
			$this->style->save();
			$this->style->analyse($other, $param);
			$this->style->setPosition();
			$this->style->FontSet();

			if ($this->style->value['margin-auto']) $align_object = 'center';

			// est-on en collapse
			$collapse = false;			
			if ($other=='table')
				$collapse = isset($this->style->value['border']['collapse']) ? $this->style->value['border']['collapse'] : false;

			// si oui il faut adapt� les borders
			if ($collapse)
			{
				$param['style']['border'] = 'none';
				$param['cellspacing'] = 0;
				$none = $this->style->readBorder('none');
				$this->style->value['border']['t'] = $none;
				$this->style->value['border']['r'] = $none;
				$this->style->value['border']['b'] = $none;
				$this->style->value['border']['l'] = $none;
			}				
			
			// si on est en mode sub_html : initialisation des dimensions et autres 
			if ($this->sub_part)
			{
				if ($this->DEBUG_actif) $this->DEBUG_add('Table n�'.$param['num'], true);
				HTML2PDF::$TABLES[$param['num']] = array();
				HTML2PDF::$TABLES[$param['num']]['border']		= isset($param['border']) ? $this->style->readBorder($param['border']) : null; // border sp�cifique si border precis� en param�tre
				HTML2PDF::$TABLES[$param['num']]['cellpadding']	= $this->style->ConvertToMM(isset($param['cellpadding']) ? $param['cellpadding'] : '1px'); // cellpadding du tableau
				HTML2PDF::$TABLES[$param['num']]['cellspacing']	= $this->style->ConvertToMM(isset($param['cellspacing']) ? $param['cellspacing'] : '2px'); // cellspacing du tableau
				HTML2PDF::$TABLES[$param['num']]['cases']		= array();				// liste des propri�t�s des cases
				HTML2PDF::$TABLES[$param['num']]['corr']		= array();				// tableau de correlation pour les colspan et rowspan
				HTML2PDF::$TABLES[$param['num']]['corr_x']		= 0;					// position dans le tableau de correlation
				HTML2PDF::$TABLES[$param['num']]['corr_y']		= 0;					// position dans le tableau de correlation
				HTML2PDF::$TABLES[$param['num']]['td_curr']		= 0;					// colonne courante
				HTML2PDF::$TABLES[$param['num']]['tr_curr']		= 0;					// ligne courante
				HTML2PDF::$TABLES[$param['num']]['curr_x']		= $this->pdf->getX();	// position courante X
				HTML2PDF::$TABLES[$param['num']]['curr_y']		= $this->pdf->getY();	// position courante Y
				HTML2PDF::$TABLES[$param['num']]['width']		= 0;					// largeur globale
				HTML2PDF::$TABLES[$param['num']]['height']		= 0;					// hauteur globale
				HTML2PDF::$TABLES[$param['num']]['align']		= $align_object;
				HTML2PDF::$TABLES[$param['num']]['marge']		= array();
				HTML2PDF::$TABLES[$param['num']]['marge']['t']	= $this->style->value['padding']['t']+$this->style->value['border']['t']['width']+HTML2PDF::$TABLES[$param['num']]['cellspacing']*0.5;
				HTML2PDF::$TABLES[$param['num']]['marge']['r']	= $this->style->value['padding']['r']+$this->style->value['border']['r']['width']+HTML2PDF::$TABLES[$param['num']]['cellspacing']*0.5;
				HTML2PDF::$TABLES[$param['num']]['marge']['b']	= $this->style->value['padding']['b']+$this->style->value['border']['b']['width']+HTML2PDF::$TABLES[$param['num']]['cellspacing']*0.5;
				HTML2PDF::$TABLES[$param['num']]['marge']['l']	= $this->style->value['padding']['l']+$this->style->value['border']['l']['width']+HTML2PDF::$TABLES[$param['num']]['cellspacing']*0.5;
				HTML2PDF::$TABLES[$param['num']]['page']		= 0;					// nombre de pages
				HTML2PDF::$TABLES[$param['num']]['new_page']	= true;					// nouvelle page pour le TR courant
				HTML2PDF::$TABLES[$param['num']]['style_value'] = null;					// style du tableau
				HTML2PDF::$TABLES[$param['num']]['thead']		= array();				// infos sur le thead
				HTML2PDF::$TABLES[$param['num']]['tfoot']		= array();				// infos sur le tfoot
				HTML2PDF::$TABLES[$param['num']]['thead']['tr']	= array();				// tr du thead
				HTML2PDF::$TABLES[$param['num']]['tfoot']['tr']	= array();				// tr du tfoot
				HTML2PDF::$TABLES[$param['num']]['thead']['height']	= 0;				// hauteur du thead
				HTML2PDF::$TABLES[$param['num']]['tfoot']['height']	= 0;				// hauteur du tfoot
				HTML2PDF::$TABLES[$param['num']]['thead']['code'] = array();			// contenu HTML du thead
				HTML2PDF::$TABLES[$param['num']]['tfoot']['code'] = array();			// contenu HTML du tfoot
				HTML2PDF::$TABLES[$param['num']]['cols']		= array();				// definition via les balises col
				$this->saveMargin($this->pdf->getlMargin(), $this->pdf->gettMargin(), $this->pdf->getrMargin());
				
				// adaptation de la largeur en fonction des marges du tableau
				$this->style->value['width']-= HTML2PDF::$TABLES[$param['num']]['marge']['l'] + HTML2PDF::$TABLES[$param['num']]['marge']['r'];
			}
			else
			{
				// on repart � la premiere page du tableau et � la premiere case
				HTML2PDF::$TABLES[$param['num']]['page'] = 0;
				HTML2PDF::$TABLES[$param['num']]['td_curr']	= 0;
				HTML2PDF::$TABLES[$param['num']]['tr_curr']	= 0;
				HTML2PDF::$TABLES[$param['num']]['td_x']		= HTML2PDF::$TABLES[$param['num']]['marge']['l']+HTML2PDF::$TABLES[$param['num']]['curr_x'];
				HTML2PDF::$TABLES[$param['num']]['td_y']		= HTML2PDF::$TABLES[$param['num']]['marge']['t']+HTML2PDF::$TABLES[$param['num']]['curr_y'];				

				// initialisation du style des bordures de la premiere partie de tableau
				$this->drawRectangle(
						HTML2PDF::$TABLES[$param['num']]['curr_x'],
						HTML2PDF::$TABLES[$param['num']]['curr_y'],
						HTML2PDF::$TABLES[$param['num']]['width'],
						isset(HTML2PDF::$TABLES[$param['num']]['height'][0]) ? HTML2PDF::$TABLES[$param['num']]['height'][0] : null,
						$this->style->value['border'],
						$this->style->value['padding'],
						0,
						$this->style->value['background']
					);

				HTML2PDF::$TABLES[$param['num']]['style_value'] = $this->style->value;
			}
			
			return true;
		}

		/**
		* balise : TABLE
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_TABLE($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;

			// restauration du style
			$this->style->load();
			$this->style->FontSet();

			// si on est en mode sub_html : initialisation des dimensions et autres 
			if ($this->sub_part)
			{
				// ajustement de la taille des cases
				$this->calculTailleCases(HTML2PDF::$TABLES[$param['num']]['cases'], HTML2PDF::$TABLES[$param['num']]['corr']);

				// calcul de la hauteur du THEAD et du TFOOT
				$lst = array('thead', 'tfoot');
				foreach($lst as $mode)
				{
					HTML2PDF::$TABLES[$param['num']][$mode]['height'] = 0;
					foreach(HTML2PDF::$TABLES[$param['num']][$mode]['tr'] as $tr)
					{
						// hauteur de la ligne tr
						$h = 0;
						for($i=0; $i<count(HTML2PDF::$TABLES[$param['num']]['cases'][$tr]); $i++)
							if (HTML2PDF::$TABLES[$param['num']]['cases'][$tr][$i]['rowspan']==1)
								$h = max($h, HTML2PDF::$TABLES[$param['num']]['cases'][$tr][$i]['h']);
						HTML2PDF::$TABLES[$param['num']][$mode]['height']+= $h;	
					}
				}

				// calcul des dimensions du tableau - Largeur
				HTML2PDF::$TABLES[$param['num']]['width'] = HTML2PDF::$TABLES[$param['num']]['marge']['l'] + HTML2PDF::$TABLES[$param['num']]['marge']['r'];
				if (isset(HTML2PDF::$TABLES[$param['num']]['cases'][0]))
					foreach(HTML2PDF::$TABLES[$param['num']]['cases'][0] as $case)
						HTML2PDF::$TABLES[$param['num']]['width']+= $case['w'];

				// positionnement du tableau horizontalement;
				$old = $this->style->getOldValues();
				$parent_w = $old['width'] ? $old['width'] : $this->pdf->getW() - $this->pdf->getlMargin() - $this->pdf->getrMargin();
				$x = HTML2PDF::$TABLES[$param['num']]['curr_x'];
				$w = HTML2PDF::$TABLES[$param['num']]['width'];
				if ($parent_w>$w)
				{
					if (HTML2PDF::$TABLES[$param['num']]['align']=='center')
						$x = $x + ($parent_w-$w)*0.5;
					else if (HTML2PDF::$TABLES[$param['num']]['align']=='right')
						$x = $x + $parent_w-$w;

					HTML2PDF::$TABLES[$param['num']]['curr_x'] = $x;
				}					


				// calcul des dimensions du tableau - hauteur du tableau sur chaque page
				HTML2PDF::$TABLES[$param['num']]['height'] = array();

				$h0 = HTML2PDF::$TABLES[$param['num']]['marge']['t'] + HTML2PDF::$TABLES[$param['num']]['marge']['b'];	// minimum de hauteur � cause des marges
				$h0+= HTML2PDF::$TABLES[$param['num']]['thead']['height'] + HTML2PDF::$TABLES[$param['num']]['tfoot']['height']; // et du tfoot et thead
				$max = $this->pdf->getH() - $this->pdf->getbMargin();			// max de hauteur par page
				$y = HTML2PDF::$TABLES[$param['num']]['curr_y'];	// position Y actuelle
				$height = $h0;
				
				// on va lire les hauteurs de chaque ligne, une � une, et voir si ca rentre sur la page.
				for($k=0; $k<count(HTML2PDF::$TABLES[$param['num']]['cases']); $k++)
				{
					// si c'est des lignes du thead ou du tfoot : on passe
					if (in_array($k, HTML2PDF::$TABLES[$param['num']]['thead']['tr'])) continue;
					if (in_array($k, HTML2PDF::$TABLES[$param['num']]['tfoot']['tr'])) continue;

					// hauteur de la ligne $k
					$th = 0;
					$h = 0;
					for($i=0; $i<count(HTML2PDF::$TABLES[$param['num']]['cases'][$k]); $i++)
					{
						$h = max($h, HTML2PDF::$TABLES[$param['num']]['cases'][$k][$i]['h']);
						
						if (HTML2PDF::$TABLES[$param['num']]['cases'][$k][$i]['rowspan']==1)
							$th = max($th, HTML2PDF::$TABLES[$param['num']]['cases'][$k][$i]['h']);
					}
			
					// si la ligne ne rentre pas dans la page
					// => la hauteur sur cette page est trouv�e, et on passe � la page d'apres
					if ($y+$h+$height>$max)
					{
						if ($height==$h0) $height = null;
						HTML2PDF::$TABLES[$param['num']]['height'][] = $height;
						$height = $h0;						
						$y = $this->margeTop;
					}
					$height+= $th;
				}
				// rajout du reste de tableau (si il existe) � la derniere page
				if ($height!=$h0 || $k==0) HTML2PDF::$TABLES[$param['num']]['height'][] = $height;
			}
			else
			{
				if (count(HTML2PDF::$TABLES[$param['num']]['tfoot']['code']))
				{
					$tmp_tr = HTML2PDF::$TABLES[$param['num']]['tr_curr'];
					$tmp_td = HTML2PDF::$TABLES[$param['num']]['td_curr'];
					$OLD_parse_pos = $this->parse_pos;
					$OLD_parse_code = $this->parsing->code;
					
					HTML2PDF::$TABLES[$param['num']]['tr_curr'] = HTML2PDF::$TABLES[$param['num']]['tfoot']['tr'][0];
					HTML2PDF::$TABLES[$param['num']]['td_curr'] = 0;
					$this->parse_pos = 0;
					$this->parsing->code = HTML2PDF::$TABLES[$param['num']]['tfoot']['code'];
					$this->makeHTMLcode();
					
					$this->parse_pos = 	$OLD_parse_pos;
					$this->parsing->code = $OLD_parse_code;
					HTML2PDF::$TABLES[$param['num']]['tr_curr'] = $tmp_tr;
					HTML2PDF::$TABLES[$param['num']]['td_curr'] = $tmp_td;
				}
					
				// determination des coordonn�es de sortie du tableau
				$x = HTML2PDF::$TABLES[$param['num']]['curr_x'] + HTML2PDF::$TABLES[$param['num']]['width'];
				if (count(HTML2PDF::$TABLES[$param['num']]['height'])>1)
					$y = $this->margeTop+HTML2PDF::$TABLES[$param['num']]['height'][count(HTML2PDF::$TABLES[$param['num']]['height'])-1];
				else if (count(HTML2PDF::$TABLES[$param['num']]['height'])==1)
					$y = HTML2PDF::$TABLES[$param['num']]['curr_y']+HTML2PDF::$TABLES[$param['num']]['height'][count(HTML2PDF::$TABLES[$param['num']]['height'])-1];
				else
					$y = HTML2PDF::$TABLES[$param['num']]['curr_y'];					

				// taille du tableau
				$this->maxX = max($this->maxX, $x);
				$this->maxY = max($this->maxY, $y);
				
				
				// nouvelle position apres le tableau
				$this->pdf->setXY($this->pdf->getlMargin(), $y);
				
				// restauration des marges
				$this->loadMargin();
				
				if ($this->DEBUG_actif) $this->DEBUG_add('Table n�'.$param['num'], false);
			}
			
			return true;
		}

				
		/**
		* balise : COL
		* mode : OUVERTURE (pas de fermeture)
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_COL($param)
		{
			$span = isset($param['span']) ? $param['span'] : 1;
			for($k=0; $k<$span; $k++)
				HTML2PDF::$TABLES[$param['num']]['cols'][] = $param;
		}
		
		/**
		* balise : TR
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_TR($param, $other = 'tr')
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;

			// analyse du style
			$this->style->save();
			$this->style->analyse($other, $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			// positionnement dans le tableau
			HTML2PDF::$TABLES[$param['num']]['tr_curr']++;
			HTML2PDF::$TABLES[$param['num']]['td_curr']= 0;
			
			// si on est pas dans un sub_html
			if (!$this->sub_part)
			{
				// Y courant apres la ligne
				$ty=null;
				for($ii=0; $ii<count(HTML2PDF::$TABLES[$param['num']]['cases'][HTML2PDF::$TABLES[$param['num']]['tr_curr']-1]); $ii++)
					$ty = max($ty, HTML2PDF::$TABLES[$param['num']]['cases'][HTML2PDF::$TABLES[$param['num']]['tr_curr']-1][$ii]['h']);	
				
				$hfoot = HTML2PDF::$TABLES[$param['num']]['tfoot']['height'];
				
				// si la ligne ne rentre pas dans la page => nouvelle page
				if (!$this->isInTfoot && HTML2PDF::$TABLES[$param['num']]['td_y'] + HTML2PDF::$TABLES[$param['num']]['marge']['b'] + $ty +$hfoot> $this->pdf->getH() - $this->pdf->getbMargin())
				{
					if (count(HTML2PDF::$TABLES[$param['num']]['tfoot']['code']))
					{
						$tmp_tr = HTML2PDF::$TABLES[$param['num']]['tr_curr'];
						$tmp_td = HTML2PDF::$TABLES[$param['num']]['td_curr'];
						$OLD_parse_pos = $this->parse_pos;
						$OLD_parse_code = $this->parsing->code;
						
						HTML2PDF::$TABLES[$param['num']]['tr_curr'] = HTML2PDF::$TABLES[$param['num']]['tfoot']['tr'][0];
						HTML2PDF::$TABLES[$param['num']]['td_curr'] = 0;
						$this->parse_pos = 0;
						$this->parsing->code = HTML2PDF::$TABLES[$param['num']]['tfoot']['code'];
						$this->isInTfoot = true;
						$this->makeHTMLcode();
						$this->isInTfoot = false;
						
						$this->parse_pos = 	$OLD_parse_pos;
						$this->parsing->code = $OLD_parse_code;
						HTML2PDF::$TABLES[$param['num']]['tr_curr'] = $tmp_tr;
						HTML2PDF::$TABLES[$param['num']]['td_curr'] = $tmp_td;
					}
					
					HTML2PDF::$TABLES[$param['num']]['new_page'] = true;
					$this->setNewPage();

					HTML2PDF::$TABLES[$param['num']]['page']++;
					HTML2PDF::$TABLES[$param['num']]['curr_y'] = $this->pdf->getY();
					HTML2PDF::$TABLES[$param['num']]['td_y'] = HTML2PDF::$TABLES[$param['num']]['curr_y']+HTML2PDF::$TABLES[$param['num']]['marge']['t'];

					// si la hauteur de cette partie a bien �t� calcul�e, on trace le cadre
					if (isset(HTML2PDF::$TABLES[$param['num']]['height'][HTML2PDF::$TABLES[$param['num']]['page']]))
					{
						$old = $this->style->value;
						$this->style->value = HTML2PDF::$TABLES[$param['num']]['style_value'];

						// initialisation du style des bordures de la premiere partie de tableau
						$this->drawRectangle(
								HTML2PDF::$TABLES[$param['num']]['curr_x'],
								HTML2PDF::$TABLES[$param['num']]['curr_y'],
								HTML2PDF::$TABLES[$param['num']]['width'],
								HTML2PDF::$TABLES[$param['num']]['height'][HTML2PDF::$TABLES[$param['num']]['page']],
								$this->style->value['border'],
								$this->style->value['padding'],
								HTML2PDF::$TABLES[$param['num']]['cellspacing']*0.5,
								$this->style->value['background']
							);
											 
						$this->style->value = $old;
					}
				}
				
				if (HTML2PDF::$TABLES[$param['num']]['new_page'] && count(HTML2PDF::$TABLES[$param['num']]['thead']['code']))
				{
					HTML2PDF::$TABLES[$param['num']]['new_page'] = false;
					$tmp_tr = HTML2PDF::$TABLES[$param['num']]['tr_curr'];
					$tmp_td = HTML2PDF::$TABLES[$param['num']]['td_curr'];
					$OLD_parse_pos = $this->parse_pos;
					$OLD_parse_code = $this->parsing->code;
					
					HTML2PDF::$TABLES[$param['num']]['tr_curr'] = HTML2PDF::$TABLES[$param['num']]['thead']['tr'][0];
					HTML2PDF::$TABLES[$param['num']]['td_curr'] = 0;
					$this->parse_pos = 0;
					$this->parsing->code = HTML2PDF::$TABLES[$param['num']]['thead']['code'];
					$this->isInThead = true;
					$this->makeHTMLcode();
					$this->isInThead = false;
					
					$this->parse_pos = 	$OLD_parse_pos;
					$this->parsing->code = $OLD_parse_code;
					HTML2PDF::$TABLES[$param['num']]['tr_curr'] = $tmp_tr;
					HTML2PDF::$TABLES[$param['num']]['td_curr'] = $tmp_td;
					HTML2PDF::$TABLES[$param['num']]['new_page'] = true;
				}
			}
			else
			{
				HTML2PDF::$TABLES[$param['num']]['cases'][HTML2PDF::$TABLES[$param['num']]['tr_curr']-1] = array();
				if (!isset(HTML2PDF::$TABLES[$param['num']]['corr'][HTML2PDF::$TABLES[$param['num']]['corr_y']]))
					HTML2PDF::$TABLES[$param['num']]['corr'][HTML2PDF::$TABLES[$param['num']]['corr_y']] = array();
					
				HTML2PDF::$TABLES[$param['num']]['corr_x']=0;
				while(isset(HTML2PDF::$TABLES[$param['num']]['corr'][HTML2PDF::$TABLES[$param['num']]['corr_y']][HTML2PDF::$TABLES[$param['num']]['corr_x']]))
					HTML2PDF::$TABLES[$param['num']]['corr_x']++;
			}							
			
			return true;
		}

		/**
		* balise : TR
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_TR($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;

			// restauration du style
			$this->style->load();
			$this->style->FontSet();			

			// si on est pas dans un sub_html
			if (!$this->sub_part)
			{
				// Y courant apres la ligne
				$ty=null;
				for($ii=0; $ii<count(HTML2PDF::$TABLES[$param['num']]['cases'][HTML2PDF::$TABLES[$param['num']]['tr_curr']-1]); $ii++)
					if (HTML2PDF::$TABLES[$param['num']]['cases'][HTML2PDF::$TABLES[$param['num']]['tr_curr']-1][$ii]['rowspan']==1)
						$ty = HTML2PDF::$TABLES[$param['num']]['cases'][HTML2PDF::$TABLES[$param['num']]['tr_curr']-1][$ii]['h'];	

				// mise � jour des coordonn�es courantes
				HTML2PDF::$TABLES[$param['num']]['td_x'] = HTML2PDF::$TABLES[$param['num']]['curr_x']+HTML2PDF::$TABLES[$param['num']]['marge']['l'];
				HTML2PDF::$TABLES[$param['num']]['td_y']+= $ty;
				HTML2PDF::$TABLES[$param['num']]['new_page'] = false;
			}
			else
			{
				HTML2PDF::$TABLES[$param['num']]['corr_y']++;	
			}
			
			return true;
		}

		/**
		* balise : TD
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_TD($param, $other = 'td')
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;

			$param['cellpadding'] = HTML2PDF::$TABLES[$param['num']]['cellpadding'].'mm';
			$param['cellspacing'] = HTML2PDF::$TABLES[$param['num']]['cellspacing'].'mm';
			
			if ($other=='li')
			{
				$special_li = true;
			}
			else
			{
				$special_li = false;
				if ($other=='li_sub')
				{
					$param['style']['border'] = 'none';
					$param['style']['background-color']		= 'transparent';
					$param['style']['background-image']		= 'none';
					$param['style']['background-position']	= '';
					$param['style']['background-repeat']	= '';
					$other = 'li';
				}
			}

			// est-on en collapse, et egalement y-a-t'il des definitions de colonne
			$x = HTML2PDF::$TABLES[$param['num']]['td_curr'];
			$y = HTML2PDF::$TABLES[$param['num']]['tr_curr']-1;
			$colspan = isset($param['colspan']) ? $param['colspan'] : 1;
			$rowspan = isset($param['rowspan']) ? $param['rowspan'] : 1;
			$collapse = false;
			if (in_array($other, array('td', 'th')))
			{
				$num_col = isset(HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['Xr']) ? HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['Xr'] : HTML2PDF::$TABLES[$param['num']]['corr_x'];
				
				// si une definition de colonne est presente
				if (isset(HTML2PDF::$TABLES[$param['num']]['cols'][$num_col]))
				{
					// on la recupere
					$col_param = HTML2PDF::$TABLES[$param['num']]['cols'][$num_col];
					
					// pour les colspan, on recupere toutes les largeurs
					$col_param['style']['width'] = array();
					for($k=0; $k<$colspan; $k++)
					{
						if (isset(HTML2PDF::$TABLES[$param['num']]['cols'][$num_col+$k]['style']['width']))
							$col_param['style']['width'][] = HTML2PDF::$TABLES[$param['num']]['cols'][$num_col+$k]['style']['width'];
					}
					
					// on les somme
					$total = '';
					$last = $this->style->getLastWidth();
					if (count($col_param['style']['width']))
					{
						$total = $col_param['style']['width'][0]; unset($col_param['style']['width'][0]);
						foreach($col_param['style']['width'] as $width)
						{
							if (substr($total, -1)=='%' && substr($width, -1)=='%')
								$total = (str_replace('%', '', $total)+str_replace('%', '', $width)).'%';
							else
								$total = ($this->style->ConvertToMM($total, $last) + $this->style->ConvertToMM($width, $last)).'mm';
						}
					}
					
					// et on recupere la largeur finale
					if ($total)
						$col_param['style']['width'] = $total;
					else
						unset($col_param['style']['width']);
					
					
					// on merge les 2 styles (col + td)
					$param['style'] = array_merge($col_param['style'], $param['style']);
					
					// si une classe est d�finie, on la merge egalement
					if (isset($col_param['class']))
						$param['class'] = $col_param['class'].(isset($param['class']) ? ' '.$param['class'] : '');
				}
				
				$collapse = isset($this->style->value['border']['collapse']) ? $this->style->value['border']['collapse'] : false;
			}


			// analyse du style
			$this->style->save();
			$heritage = null;
			if (in_array($other, array('td', 'th')))
			{
				$heritage = array();
				
				$old = $this->style->getLastValue('background');
				if ($old && ($old['color'] || $old['image']))
					$heritage['background'] = $old;
					
				if (HTML2PDF::$TABLES[$param['num']]['border'])
				{
					$heritage['border'] = array();
					$heritage['border']['l'] = HTML2PDF::$TABLES[$param['num']]['border'];
					$heritage['border']['t'] = HTML2PDF::$TABLES[$param['num']]['border'];
					$heritage['border']['r'] = HTML2PDF::$TABLES[$param['num']]['border'];
					$heritage['border']['b'] = HTML2PDF::$TABLES[$param['num']]['border'];
				} 
			} 
			$return = $this->style->analyse($other, $param, $heritage);

			if ($special_li)
			{
				$this->style->value['width']-= $this->style->ConvertToMM($this->listeGetWidth());
				$this->style->value['width']-= $this->style->ConvertToMM($this->listeGetPadding());
			}
			$this->style->setPosition();
			$this->style->FontSet();
			
			// si on est en collapse : modification du style
			if ($collapse)
			{
				if (!$this->sub_part)
				{
					if (
							(HTML2PDF::$TABLES[$param['num']]['tr_curr']>1 && !HTML2PDF::$TABLES[$param['num']]['new_page']) ||
							(!$this->isInThead && count(HTML2PDF::$TABLES[$param['num']]['thead']['code']))
						)
					{
						$this->style->value['border']['t'] = $this->style->readBorder('none');
					}
				}
			
				if (HTML2PDF::$TABLES[$param['num']]['td_curr']>0)
				{
					if (!$return) $this->style->value['width']+= $this->style->value['border']['l']['width'];
					$this->style->value['border']['l'] = $this->style->readBorder('none');
				}
			}	
			
			$marge = array();
			$marge['t'] = $this->style->value['padding']['t']+0.5*HTML2PDF::$TABLES[$param['num']]['cellspacing']+$this->style->value['border']['t']['width'];
			$marge['r'] = $this->style->value['padding']['r']+0.5*HTML2PDF::$TABLES[$param['num']]['cellspacing']+$this->style->value['border']['r']['width'];
			$marge['b'] = $this->style->value['padding']['b']+0.5*HTML2PDF::$TABLES[$param['num']]['cellspacing']+$this->style->value['border']['b']['width'];
			$marge['l'] = $this->style->value['padding']['l']+0.5*HTML2PDF::$TABLES[$param['num']]['cellspacing']+$this->style->value['border']['l']['width'];

			// si on est dans un sub_html
			if ($this->sub_part)
			{
				// on se positionne dans le tableau
				HTML2PDF::$TABLES[$param['num']]['td_curr']++;
				HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x] = array();
				HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['w'] = 0;
				HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['h'] = 0;
				HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['dw'] = 0;
				HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['colspan'] = $colspan;
				HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['rowspan'] = $rowspan;
				HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['Xr'] = HTML2PDF::$TABLES[$param['num']]['corr_x'];
				HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['Yr'] = HTML2PDF::$TABLES[$param['num']]['corr_y'];
				
				for($j=0; $j<$rowspan; $j++)
				{
					for($i=0; $i<$colspan; $i++)
					{
						HTML2PDF::$TABLES[$param['num']]['corr']
							[HTML2PDF::$TABLES[$param['num']]['corr_y']+$j]
							[HTML2PDF::$TABLES[$param['num']]['corr_x']+$i] = ($i+$j>0) ? '' : array($x,$y,$colspan,$rowspan);
					}
				}
				HTML2PDF::$TABLES[$param['num']]['corr_x']+= $colspan;
				while(isset(HTML2PDF::$TABLES[$param['num']]['corr'][HTML2PDF::$TABLES[$param['num']]['corr_y']][HTML2PDF::$TABLES[$param['num']]['corr_x']]))
					HTML2PDF::$TABLES[$param['num']]['corr_x']++;

				// on extrait tout ce qui est contenu dans le TD
				// on en cr�� un sous HTML que l'on transforme en PDF
				// pour analyse les dimensions
				// et les r�cup�rer dans le tableau global.
				$level = $this->parsing->getLevel($this->temp_pos);
				$this->CreateSubHTML($this->sub_html);
				$this->sub_html->parsing->code = $level;
				$this->sub_html->MakeHTMLcode();
				$this->temp_pos+= count($level);
			}
			else
			{
				// on se positionne dans le tableau
				HTML2PDF::$TABLES[$param['num']]['td_curr']++;
				HTML2PDF::$TABLES[$param['num']]['td_x']+= HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['dw'];
				
				// initialisation du style des bordures de la premiere partie de tableau
				$this->drawRectangle(
						HTML2PDF::$TABLES[$param['num']]['td_x'],
						HTML2PDF::$TABLES[$param['num']]['td_y'],
						HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['w'],
						HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['h'],
						$this->style->value['border'],
						$this->style->value['padding'],
						HTML2PDF::$TABLES[$param['num']]['cellspacing']*0.5,
						$this->style->value['background']
					);
				

				$this->style->value['width'] = HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['w'] - $marge['l'] - $marge['r'];

				// limitation des marges aux dimensions de la case
				$mL = HTML2PDF::$TABLES[$param['num']]['td_x']+$marge['l'];
				$mR = $this->pdf->getW() - $mL - $this->style->value['width'];
				$this->saveMargin($mL, 0, $mR);
				
				// positionnement en fonction
				$h_corr = HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['h'];
				$h_reel = HTML2PDF::$TABLES[$param['num']]['cases'][$y][$x]['real_h'];
				switch($this->style->value['vertical-align'])
				{
					case 'bottom':
						$y_corr = $h_corr-$h_reel;
						break;
						
					case 'middle':
						$y_corr = ($h_corr-$h_reel)*0.5;
						break;
						
					case 'top':
					default:
						$y_corr = 0;
						break;	
				}

				$x = HTML2PDF::$TABLES[$param['num']]['td_x']+$marge['l'];
				$y = HTML2PDF::$TABLES[$param['num']]['td_y']+$marge['t']+$y_corr;
				$this->pdf->setXY($x, $y);
				$this->setNewPositionForNewLine();
			}
			
			return true;
		}

		/**
		* balise : TD
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_TD($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;

			// r�cup�ration de la marge
			$marge = array();
			$marge['t'] = $this->style->value['padding']['t']+0.5*HTML2PDF::$TABLES[$param['num']]['cellspacing']+$this->style->value['border']['t']['width'];
			$marge['r'] = $this->style->value['padding']['r']+0.5*HTML2PDF::$TABLES[$param['num']]['cellspacing']+$this->style->value['border']['r']['width'];
			$marge['b'] = $this->style->value['padding']['b']+0.5*HTML2PDF::$TABLES[$param['num']]['cellspacing']+$this->style->value['border']['b']['width'];
			$marge['l'] = $this->style->value['padding']['l']+0.5*HTML2PDF::$TABLES[$param['num']]['cellspacing']+$this->style->value['border']['l']['width'];
			$marge['t']+= 0.001;
			$marge['r']+= 0.001;
			$marge['b']+= 0.001;
			$marge['l']+= 0.001;

			// si on est dans un sub_html
			if ($this->sub_part)
			{
				if ($this->testTDin1page && $this->sub_html->pdf->getPage()>1)
					throw new HTML2PDF_exception(7); 
				
				// dimentions de cette case
				$w0 = $this->sub_html->maxX + $marge['l'] + $marge['r'];
				$h0 = $this->sub_html->maxY + $marge['t'] + $marge['b'];
	
				// dimensions impos�es par le style
				$w2 = $this->style->value['width'] + $marge['l'] + $marge['r'];
				$h2 = $this->style->value['height'] + $marge['t'] + $marge['b'];
	
				// dimension finale de la case = max des 2 ci-dessus
				HTML2PDF::$TABLES[$param['num']]['cases'][HTML2PDF::$TABLES[$param['num']]['tr_curr']-1][HTML2PDF::$TABLES[$param['num']]['td_curr']-1]['w'] = max(array($w0, $w2));
				HTML2PDF::$TABLES[$param['num']]['cases'][HTML2PDF::$TABLES[$param['num']]['tr_curr']-1][HTML2PDF::$TABLES[$param['num']]['td_curr']-1]['h'] = max(array($h0, $h2));

				HTML2PDF::$TABLES[$param['num']]['cases'][HTML2PDF::$TABLES[$param['num']]['tr_curr']-1][HTML2PDF::$TABLES[$param['num']]['td_curr']-1]['real_w'] = $w0;
				HTML2PDF::$TABLES[$param['num']]['cases'][HTML2PDF::$TABLES[$param['num']]['tr_curr']-1][HTML2PDF::$TABLES[$param['num']]['td_curr']-1]['real_h'] = $h0;

				// suppresion du sous_html
				$this->destroySubHTML($this->sub_html);
			}
			else
			{
				$this->loadMargin();
				//positionnement
				HTML2PDF::$TABLES[$param['num']]['td_x']+= HTML2PDF::$TABLES[$param['num']]['cases'][HTML2PDF::$TABLES[$param['num']]['tr_curr']-1][HTML2PDF::$TABLES[$param['num']]['td_curr']-1]['w'];
			}

			// restauration du style
			$this->style->load();
			$this->style->FontSet();	
			
			return true;
		}
		
		protected function calculTailleCases(&$cases, &$corr)
		{
/*			// construction d'un tableau de correlation
			$corr = array();

			// on fait correspondre chaque case d'un tableau norm� aux cases r�elles, en prennant en compte les colspan et rowspan
			$Yr=0;
			for($y=0; $y<count($cases); $y++)
			{
				$Xr=0; 	while(isset($corr[$Yr][$Xr])) $Xr++;
				
				for($x=0; $x<count($cases[$y]); $x++)
				{
					for($j=0; $j<$cases[$y][$x]['rowspan']; $j++)
					{
						for($i=0; $i<$cases[$y][$x]['colspan']; $i++)
						{
							$corr[$Yr+$j][$Xr+$i] = ($i+$j>0) ? '' : array($x, $y, $cases[$y][$x]['colspan'], $cases[$y][$x]['rowspan']);
						}
					}
					$Xr+= $cases[$y][$x]['colspan'];
					while(isset($corr[$Yr][$Xr])) $Xr++;
				}
				$Yr++;
			}
*/			
			if (!isset($corr[0])) return true;
			
			// on d�termine, pour les cases sans colspan, la largeur maximale de chaque colone
			$sw = array();
			for($x=0; $x<count($corr[0]); $x++)
			{
				$m=0;
				for($y=0; $y<count($corr); $y++)
					if (isset($corr[$y][$x]) && is_array($corr[$y][$x]) && $corr[$y][$x][2]==1)
						$m = max($m, $cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['w']);				
				$sw[$x] = $m;	
			}

			// on v�rifie que cette taille est valide avec les colones en colspan
			for($x=0; $x<count($corr[0]); $x++)
			{
				for($y=0; $y<count($corr); $y++)
				{
					if (isset($corr[$y][$x]) && is_array($corr[$y][$x]) && $corr[$y][$x][2]>1)
					{
						// somme des colonnes correspondant au colspan
						$s = 0; for($i=0; $i<$corr[$y][$x][2]; $i++) $s+= $sw[$x+$i];
						
						// si la somme est inf�rieure � la taille necessaire => r�gle de 3 pour adapter
						if ($s>0 && $s<$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['w'])
							for($i=0; $i<$corr[$y][$x][2]; $i++)
								$sw[$x+$i] = $sw[$x+$i]/$s*$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['w'];
					}
				}
			}

			// on applique les nouvelles largeurs
			for($x=0; $x<count($corr[0]); $x++)
			{
				for($y=0; $y<count($corr); $y++)
				{
					if (isset($corr[$y][$x]) && is_array($corr[$y][$x]))
					{
						if ($corr[$y][$x][2]==1)
						{
							$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['w'] = $sw[$x];
						}
						else
						{
							// somme des colonnes correspondant au colspan
							$s = 0; for($i=0; $i<$corr[$y][$x][2]; $i++) $s+= $sw[$x+$i];
							$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['w'] = $s;
						}
					}
				}
			}

			// on d�termine, pour les cases sans rowspan, la hauteur maximale de chaque colone
			$sh = array();
			for($y=0; $y<count($corr); $y++)
			{
				$m=0;
				for($x=0; $x<count($corr[0]); $x++)
					if (isset($corr[$y][$x]) && is_array($corr[$y][$x]) && $corr[$y][$x][3]==1)
						$m = max($m, $cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['h']);
				$sh[$y] = $m;	
			}


			// on v�rifie que cette taille est valide avec les lignes en rowspan
			for($y=0; $y<count($corr); $y++)
			{
				for($x=0; $x<count($corr[0]); $x++)
				{
					if (isset($corr[$y][$x]) && is_array($corr[$y][$x]) && $corr[$y][$x][3]>1)
					{
						// somme des colonnes correspondant au colspan
						$s = 0; for($i=0; $i<$corr[$y][$x][3]; $i++) $s+= $sh[$y+$i];
						
						// si la somme est inf�rieure � la taille necessaire => r�gle de 3 pour adapter
						if ($s>0 && $s<$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['h'])
							for($i=0; $i<$corr[$y][$x][3]; $i++)
								$sh[$y+$i] = $sh[$y+$i]/$s*$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['h'];
					}
				}
			}
			

			// on applique les nouvelles hauteurs
			for($y=0; $y<count($corr); $y++)
			{
				for($x=0; $x<count($corr[0]); $x++)
				{
					if (isset($corr[$y][$x]) && is_array($corr[$y][$x]))
					{
						if ($corr[$y][$x][3]==1)
						{
							$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['h'] = $sh[$y];
						}
						else
						{
							// somme des lignes correspondant au rowspan
							$s = 0; for($i=0; $i<$corr[$y][$x][3]; $i++) $s+= $sh[$y+$i];
							$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['h'] = $s;
							
							for($j=1; $j<$corr[$y][$x][3]; $j++)
							{
								$tx = $x+1;
								$ty = $y+$j;
								for(true; isset($corr[$ty][$tx]) && !is_array($corr[$ty][$tx]); $tx++);
								if (isset($corr[$ty][$tx])) $cases[$corr[$ty][$tx][1]][$corr[$ty][$tx][0]]['dw']+= $cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['w'];
																	
							}
						}
					}
				}
			}		
		}

		/**
		* balise : TH
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_TH($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;
			// identique � TD mais en gras
			if (!isset($param['style']['font-weight'])) $param['style']['font-weight'] = 'bold';
			$this->o_TD($param, 'th');
			
			return true;
		}	

		/**
		* balise : TH
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_TH($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;
			// identique � TD
			$this->c_TD($param);			
			
			return true;
		}

		/**
		* balise : IMG
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_IMG($param)
		{
			// analyse du style
			$src	= str_replace('&amp;', '&', $param['src']);	

			$this->style->save();
			$this->style->value['width']	= 0;
			$this->style->value['height']	= 0;
			$this->style->value['border']	= array(
													'type'	=> 'none',
													'width'	=> 0,
													'color'	=> array(0, 0, 0),
												);
			$this->style->value['background'] = array(
													'color'		=> null,
													'image'		=> null,
													'position'	=> null,
													'repeat'	=> null
												);
			$this->style->analyse('img', $param);
			$this->style->setPosition();
			$this->style->FontSet();

			// affichage de l'image
			$res = $this->Image($src, isset($param['sub_li']));
			if (!$res) return $res;

			// restauration du style
			$this->style->load();
			$this->style->FontSet();
			$this->maxE++; 
			
			return true;
		}
		
		/**
		* balise : SELECT
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_SELECT($param)
		{
			// preparation du champs
			if (!isset($param['name']))		$param['name']	= 'champs_pdf_'.(count($this->lstChamps)+1);
			
			$param['name'] = strtolower($param['name']);
			
			if (isset($this->lstChamps[$param['name']]))
				$this->lstChamps[$param['name']]++;
			else
				$this->lstChamps[$param['name']] = 1;
				
			$this->style->save();
			$this->style->analyse('select', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			$this->lstSelect = array();
			$this->lstSelect['name']	= $param['name'];
			$this->lstSelect['multi']	= isset($param['multiple']) ? true : false;
			$this->lstSelect['size']	= isset($param['size']) ? $param['size'] : 1;
			$this->lstSelect['options']	= array();

			if ($this->lstSelect['multi'] && $this->lstSelect['size']<3) $this->lstSelect['size'] = 3;
			
			return true;
		}
		
		/**
		* balise : OPTION
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_OPTION($param)
		{
			// on extrait tout ce qui est contenu dans l'option
			$level = $this->parsing->getLevel($this->parse_pos);
			$this->parse_pos+= count($level); 
			$value = isset($param['value']) ? $param['value'] : 'auto_opt_'.(count($this->lstSelect)+1);
			
			$this->lstSelect['options'][$value] = isset($level[0]['param']['txt']) ? $level[0]['param']['txt'] : '';
			
			return true;
		}
		
		/**
		* balise : OPTION
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_OPTION($param) { return true; }
				
		/**
		* balise : SELECT
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_SELECT()
		{
			// position d'affichage
			$x = $this->pdf->getX();
			$y = $this->pdf->getY();
			$f = 1.08*$this->style->value['font-size'];

			$w = $this->style->value['width']; if (!$w) $w = 50; 
			$h = ($f*1.07*$this->lstSelect['size'] + 1);
			$opts = array();
			if ($this->lstSelect['multi']) $opts['multipleSelection'] = 'true';
			
			if ($this->lstSelect['size']>1)
				$this->pdf->ListBox ($this->lstSelect['name'], $w, $h, $this->lstSelect['options'], $opts);
			else
				$this->pdf->ComboBox($this->lstSelect['name'], $w, $h, $this->lstSelect['options']);
							
			$this->maxX = max($this->maxX, $x+$w);
			$this->maxY = max($this->maxY, $y+$h);
 			$this->maxH = max($this->maxH, $h);
			$this->pdf->setX($x+$w);
			
			$this->style->load();
			$this->style->FontSet();
			
			$this->lstSelect = array();
			
			return true;
		}

		/**
		* balise : TEXTAREA
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_TEXTAREA($param)
		{
			// preparation du champs
			if (!isset($param['name']))		$param['name']	= 'champs_pdf_'.(count($this->lstChamps)+1);
			
			$param['name'] = strtolower($param['name']);
			
			if (isset($this->lstChamps[$param['name']]))
				$this->lstChamps[$param['name']]++;
			else
				$this->lstChamps[$param['name']] = 1;
				
			$this->style->save();
			$this->style->analyse('textarea', $param);
			$this->style->setPosition();
			$this->style->FontSet();

			// position d'affichage
			$x = $this->pdf->getX();
			$y = $this->pdf->getY();
			$fx = 0.65*$this->style->value['font-size'];
			$fy = 1.08*$this->style->value['font-size'];

			// on extrait tout ce qui est contenu dans le textarea
			$level = $this->parsing->getLevel($this->parse_pos);
			$this->parse_pos+= count($level);

			$w = $fx*(isset($param['cols']) ? $param['cols'] : 22)+1; 
			$h = $fy*1.07*(isset($param['rows']) ? $param['rows'] : 3)+3;
			
//			if ($this->style->value['width']) $w = $this->style->value['width'];
//			if ($this->style->value['height']) $h = $this->style->value['height'];
			
			$prop = array();
			$prop['multiline'] = true;
			$prop['value'] = isset($level[0]['param']['txt']) ? $level[0]['param']['txt'] : '';
			
			$this->pdf->TextField($param['name'], $w, $h, $prop, array(), $x, $y);
					
			$this->maxX = max($this->maxX, $x+$w);
			$this->maxY = max($this->maxY, $y+$h);
 			$this->maxH = max($this->maxH, $h);
			$this->pdf->setX($x+$w);
					
			return true;
		}
		
		/**
		* balise : TEXTAREA
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_TEXTAREA()
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
						
		/**
		* balise : INPUT
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_INPUT($param)
		{
			// preparation du champs
			if (!isset($param['name']))		$param['name']	= 'champs_pdf_'.(count($this->lstChamps)+1);
			if (!isset($param['value']))	$param['value']	= '';
			if (!isset($param['type']))		$param['type']	= 'text';
			
			$param['name'] = strtolower($param['name']);
			$param['type'] = strtolower($param['type']);

			if (!in_array($param['type'], array('text', 'checkbox', 'radio', 'hidden', 'submit', 'reset', 'button'))) $param['type'] = 'text';

			if (isset($this->lstChamps[$param['name']]))
				$this->lstChamps[$param['name']]++;
			else
				$this->lstChamps[$param['name']] = 1;

			$this->style->save();
			$this->style->analyse('input', $param);
			$this->style->setPosition();
			$this->style->FontSet();
			
			$name = $param['name'];
		
			// position d'affichage
			$x = $this->pdf->getX();
			$y = $this->pdf->getY();
			$f = 1.08*$this->style->value['font-size'];
			
			switch($param['type'])
			{
				case 'checkbox':
					$w = 3;
					$h = $w;
					if ($h<$f) $y+= ($f-$h)*0.5;
					$this->pdf->CheckBox($name, $w, isset($param['checked']), array(), array(), ($param['value'] ? $param['value'] : 'Yes'), $x, $y);
					break;
				
				case 'radio':
					$w = 3;
					$h = $w;
					if ($h<$f) $y+= ($f-$h)*0.5;
					$this->pdf->RadioButton($name, $w, array(), array(), ($param['value'] ? $param['value'] : 'On'), isset($param['selected']), $x, $y);
					break;
					
				case 'hidden':
					$w = 0;
					$h = 0;
					$prop = array();
					$prop['value'] = $param['value'];
					$this->pdf->TextField($name, $w, $h, $prop, array(), $x, $y);
					break;
					
				case 'text':
					$w = $this->style->value['width']; if (!$w) $w = 40; 
					$h = $f*1.3;
					$prop = array();
					$prop['value'] = $param['value'];
					$this->pdf->TextField($name, $w, $h, $prop, array(), $x, $y);
					break;

				case 'submit':
					$w = $this->style->value['width'];	if (!$w) $w = 40; 
					$h = $this->style->value['height'];	if (!$h) $h = $f*1.3;
					$action = array('S'=>'SubmitForm', 'F'=>$this->isInForm, 'Flags'=>array('ExportFormat'));
					$this->pdf->Button($name, $w, $h, $param['value'], $action, array(), array(), $x, $y);
					break;
					
				case 'reset':
					$w = $this->style->value['width'];	if (!$w) $w = 40; 
					$h = $this->style->value['height'];	if (!$h) $h = $f*1.3;
					$action = array('S'=>'ResetForm');
					$this->pdf->Button($name, $w, $h, $param['value'], $action, array(), array(), $x, $y);
					break;
					
				case 'button':
					$w = $this->style->value['width'];	if (!$w) $w = 40; 
					$h = $this->style->value['height'];	if (!$h) $h = $f*1.3;
					$action = isset($param['onclick']) ? $param['onclick'] : '';
					$this->pdf->Button($name, $w, $h, $param['value'], $action, array(), array(), $x, $y);
					break;
					
				default:
					$w = 0;
					$h = 0;
					break;
			}
			
			$this->maxX = max($this->maxX, $x+$w);
			$this->maxY = max($this->maxY, $y+$h);
 			$this->maxH = max($this->maxH, $h);
			$this->pdf->setX($x+$w);
			
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		
		/**
		* balise : DRAW
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_DRAW($param)
		{
			if ($this->forOneLine) return false;
			if ($this->DEBUG_actif) $this->DEBUG_add('DRAW', true);
			
			$this->style->save();
			$this->style->analyse('draw', $param);
			$this->style->FontSet();
			
			$align_object = null;
			if ($this->style->value['margin-auto']) $align_object = 'center';
			
			$over_w = $this->style->value['width'];
			$over_h = $this->style->value['height'];
			$this->style->value['old_maxX'] = $this->maxX;
			$this->style->value['old_maxY'] = $this->maxY;
			$this->style->value['old_maxH'] = $this->maxH;

			$w = $this->style->value['width'];
			$h = $this->style->value['height'];
			
			if (!$this->style->value['position'])
			{
				if (
					$w < ($this->pdf->getW() - $this->pdf->getlMargin()-$this->pdf->getrMargin()) &&
					$this->pdf->getX() + $w>=($this->pdf->getW() - $this->pdf->getrMargin())
					)
					$this->o_BR(array());
	
				if (
						($h < ($this->pdf->getH() - $this->pdf->gettMargin()-$this->pdf->getbMargin())) &&
						($this->pdf->getY() + $h>=($this->pdf->getH() - $this->pdf->getbMargin())) && 
						!$this->isInOverflow
					)
					$this->setNewPage();
				
				// en cas d'alignement => correction
				$old = $this->style->getOldValues();
				$parent_w = $old['width'] ? $old['width'] : $this->pdf->getW() - $this->pdf->getlMargin() - $this->pdf->getrMargin();
				
				if ($parent_w>$w)
				{				
					if ($align_object=='center')		$this->pdf->setX($this->pdf->getX() + ($parent_w-$w)*0.5);
					else if ($align_object=='right')	$this->pdf->setX($this->pdf->getX() + $parent_w-$w);
				}
				
				$this->style->setPosition();
			}
			else
			{
				// en cas d'alignement => correction
				$old = $this->style->getOldValues();
				$parent_w = $old['width'] ? $old['width'] : $this->pdf->getW() - $this->pdf->getlMargin() - $this->pdf->getrMargin();
				
				if ($parent_w>$w)
				{				
					if ($align_object=='center')		$this->pdf->setX($this->pdf->getX() + ($parent_w-$w)*0.5);
					else if ($align_object=='right')	$this->pdf->setX($this->pdf->getX() + $parent_w-$w);
				}
				
				$this->style->setPosition();
				$this->saveMax();
				$this->maxX = 0;
				$this->maxY = 0;
				$this->maxH = 0;
				$this->maxE = 0;
			}		
			
			// initialisation du style des bordures de la div
			$this->drawRectangle(
					$this->style->value['x'],
					$this->style->value['y'],
					$this->style->value['width'],
					$this->style->value['height'],
					$this->style->value['border'],
					$this->style->value['padding'],
					0,
					$this->style->value['background']
				);
			
			$marge = array();
			$marge['l'] = $this->style->value['border']['l']['width'];
			$marge['r'] = $this->style->value['border']['r']['width'];
			$marge['t'] = $this->style->value['border']['t']['width'];
			$marge['b'] = $this->style->value['border']['b']['width'];

			$this->style->value['width'] -= $marge['l']+$marge['r'];
			$this->style->value['height']-= $marge['t']+$marge['b'];
		
			$over_w-= $marge['l']+$marge['r'];
			$over_h-= $marge['t']+$marge['b'];
			$this->pdf->clippingPathOpen(
				$this->style->value['x']+$marge['l'],
				$this->style->value['y']+$marge['t'],
				$this->style->value['width'],
				$this->style->value['height']
			);		

			// limitation des marges aux dimensions du contenu
			$mL = $this->style->value['x']+$marge['l'];
			$mR = $this->pdf->getW() - $mL - $over_w;
			
			$x = $this->style->value['x']+$marge['l'];
			$y = $this->style->value['y']+$marge['t'];
			$this->saveMargin($mL, 0, $mR);
			$this->pdf->setXY($x, $y);
			
			$this->isInDraw = array(
				'x' => $x,
				'y' => $y,
				'w' => $over_w,
				'h' => $over_h,
			);
			$this->pdf->doTransform(array(1,0,0,1,$x,$y));
			$this->pdf->SetAlpha(1.);
			return true;
		}
			
		/**
		* balise : DRAW
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_DRAW($param)
		{
			if ($this->forOneLine) return false;

			$this->pdf->SetAlpha(1.);
			$this->pdf->undoTransform();
			$this->pdf->clippingPathClose();
			
			$this->maxX = $this->style->value['old_maxX'];
			$this->maxY = $this->style->value['old_maxY'];
			$this->maxH = $this->style->value['old_maxH'];
			
			$marge = array();
			$marge['l'] = $this->style->value['border']['l']['width'];
			$marge['r'] = $this->style->value['border']['r']['width'];
			$marge['t'] = $this->style->value['border']['t']['width'];
			$marge['b'] = $this->style->value['border']['b']['width'];
			
			$x = $this->style->value['x'];
			$y = $this->style->value['y'];
			$w = $this->style->value['width']+$marge['l']+$marge['r'];
			$h = $this->style->value['height']+$marge['t']+$marge['b'];
			
			if ($this->style->value['position']!='absolute')
			{
				// position
				$this->pdf->setXY($x+$w, $y);
				 	
				// position MAX
				$this->maxX = max($this->maxX, $x+$w);
				$this->maxY = max($this->maxY, $y+$h);
		 		$this->maxH = max($this->maxH, $h);
			}
			else
			{
				// position
				$this->pdf->setXY($this->style->value['xc'], $this->style->value['yc']);
				 	
				$this->loadMax();
			}
	 	
	 		$block = ($this->style->value['display']!='inline' && $this->style->value['position']!='absolute');
	 		
	 		$this->style->load();
			$this->style->FontSet();
			$this->loadMargin();
			
			if ($block) $this->o_BR(array());
			if ($this->DEBUG_actif) $this->DEBUG_add('DRAW', false);
			
			$this->isInDraw = null;
			
			return true;
		}
		
		/**
		* balise : LINE
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_LINE($param)
		{
			if (!$this->isInDraw) throw new HTML2PDF_exception(8, 'LINE');
			$this->pdf->doTransform(isset($param['transform']) ? $this->_prepareTransform($param['transform']) : null);
			$this->style->save();
	 		$styles = $this->style->getSvgStyle('path', $param);
	 		$styles['fill'] = null;
			$style = $this->pdf->svgSetStyle($styles);
	 					
			$x1 = isset($param['x1']) ? $this->style->ConvertToMM($param['x1'], $this->isInDraw['w']) : 0.;
			$y1 = isset($param['y1']) ? $this->style->ConvertToMM($param['y1'], $this->isInDraw['h']) : 0.;
			$x2 = isset($param['x2']) ? $this->style->ConvertToMM($param['x2'], $this->isInDraw['w']) : 0.;
			$y2 = isset($param['y2']) ? $this->style->ConvertToMM($param['y2'], $this->isInDraw['h']) : 0.;
			$this->pdf->svgLine($x1, $y1, $x2, $y2);

			$this->pdf->undoTransform();
			$this->style->load();
		}
		
		/**
		* balise : RECT
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_RECT($param)
		{
			if (!$this->isInDraw) throw new HTML2PDF_exception(8, 'RECT');
			$this->pdf->doTransform(isset($param['transform']) ? $this->_prepareTransform($param['transform']) : null);
			$this->style->save();
	 		$styles = $this->style->getSvgStyle('path', $param);
			$style = $this->pdf->svgSetStyle($styles);
	 					
			$x = isset($param['x']) ? $this->style->ConvertToMM($param['x'], $this->isInDraw['w']) : 0.;
			$y = isset($param['y']) ? $this->style->ConvertToMM($param['y'], $this->isInDraw['h']) : 0.;
			$w = isset($param['w']) ? $this->style->ConvertToMM($param['w'], $this->isInDraw['w']) : 0.;
			$h = isset($param['h']) ? $this->style->ConvertToMM($param['h'], $this->isInDraw['h']) : 0.;
				
			$this->pdf->svgRect($x, $y, $w, $h, $style);

			$this->pdf->undoTransform();
			$this->style->load();
		}
		
		/**
		* balise : CIRCLE
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_CIRCLE($param)
		{
			if (!$this->isInDraw) throw new HTML2PDF_exception(8, 'CIRCLE');
			$this->pdf->doTransform(isset($param['transform']) ? $this->_prepareTransform($param['transform']) : null);
			$this->style->save();
	 		$styles = $this->style->getSvgStyle('path', $param);
			$style = $this->pdf->svgSetStyle($styles);
	 					
			$cx = isset($param['cx']) ? $this->style->ConvertToMM($param['cx'], $this->isInDraw['w']) : 0.;
			$cy = isset($param['cy']) ? $this->style->ConvertToMM($param['cy'], $this->isInDraw['h']) : 0.;
			$r = isset($param['r']) ? $this->style->ConvertToMM($param['r'], $this->isInDraw['w']) : 0.;
			$this->pdf->svgEllipse($cx, $cy, $r, $r, $style);
			
			$this->pdf->undoTransform();
			$this->style->load();
		}
		
		/**
		* balise : ELLIPSE
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_ELLIPSE($param)
		{
			if (!$this->isInDraw) throw new HTML2PDF_exception(8, 'ELLIPSE');
			$this->pdf->doTransform(isset($param['transform']) ? $this->_prepareTransform($param['transform']) : null);
			$this->style->save();
	 		$styles = $this->style->getSvgStyle('path', $param);
			$style = $this->pdf->svgSetStyle($styles);
	 					
			$cx = isset($param['cx']) ? $this->style->ConvertToMM($param['cx'], $this->isInDraw['w']) : 0.;
			$cy = isset($param['cy']) ? $this->style->ConvertToMM($param['cy'], $this->isInDraw['h']) : 0.;
			$rx = isset($param['ry']) ? $this->style->ConvertToMM($param['rx'], $this->isInDraw['w']) : 0.;
			$ry = isset($param['rx']) ? $this->style->ConvertToMM($param['ry'], $this->isInDraw['h']) : 0.;
			$this->pdf->svgEllipse($cx, $cy, $rx, $ry, $style);
						
			$this->pdf->undoTransform();
			$this->style->load();
		}
		
		
			/**
		* balise : POLYLINE
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_POLYLINE($param)
		{
			if (!$this->isInDraw) throw new HTML2PDF_exception(8, 'POLYGON');
			$this->pdf->doTransform(isset($param['transform']) ? $this->_prepareTransform($param['transform']) : null);
			$this->style->save();
	 		$styles = $this->style->getSvgStyle('path', $param);
			$style = $this->pdf->svgSetStyle($styles);
	 		
			$path = isset($param['points']) ? $param['points'] : null;
			if ($path)
			{
				$path = str_replace(',', ' ', $path);
				$path = preg_replace('/[\s]+/', ' ', trim($path));
				
				// decoupage et nettoyage
				$path = explode(' ', $path);
				foreach($path as $k => $v)
				{
					$path[$k] = trim($v);
					if ($path[$k]==='') unset($path[$k]);
				}
				$path = array_values($path);

				$actions = array();
				for($k=0; $k<count($path); $k+=2)
				{
					$actions[] = array(($k ? 'L' : 'M') ,
										$this->style->ConvertToMM($path[$k+0], $this->isInDraw['w']),
										$this->style->ConvertToMM($path[$k+1], $this->isInDraw['h'])); 	
				}

				// on trace
				$this->pdf->svgPolygone($actions, $style);
			}
			
			$this->pdf->undoTransform();
			$this->style->load();
		}
		
		/**
		* balise : POLYGON
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_POLYGON($param)
		{
			if (!$this->isInDraw) throw new HTML2PDF_exception(8, 'POLYGON');
			$this->pdf->doTransform(isset($param['transform']) ? $this->_prepareTransform($param['transform']) : null);
			$this->style->save();
	 		$styles = $this->style->getSvgStyle('path', $param);
			$style = $this->pdf->svgSetStyle($styles);
			
			$path = isset($param['points']) ? $param['points'] : null;
			if ($path)
			{
				$path = str_replace(',', ' ', $path);
				$path = preg_replace('/[\s]+/', ' ', trim($path));
				
				// decoupage et nettoyage
				$path = explode(' ', $path);
				foreach($path as $k => $v)
				{
					$path[$k] = trim($v);
					if ($path[$k]==='') unset($path[$k]);
				}
				$path = array_values($path);

				$actions = array();
				for($k=0; $k<count($path); $k+=2)
				{
					$actions[] = array(($k ? 'L' : 'M') ,
										$this->style->ConvertToMM($path[$k+0], $this->isInDraw['w']),
										$this->style->ConvertToMM($path[$k+1], $this->isInDraw['h'])); 	
				}
				$actions[] = array('z');
				// on trace
				$this->pdf->svgPolygone($actions, $style);
			}
			
			$this->pdf->undoTransform();
			$this->style->load();
		}

		/**
		* balise : PATH
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_PATH($param)
		{
			if (!$this->isInDraw) throw new HTML2PDF_exception(8, 'PATH');
			$this->pdf->doTransform(isset($param['transform']) ? $this->_prepareTransform($param['transform']) : null);
			$this->style->save();
	 		$styles = $this->style->getSvgStyle('path', $param);
			$style = $this->pdf->svgSetStyle($styles);
	 					
			$path = isset($param['d']) ? $param['d'] : null;
			if ($path)
			{
				// preparation
				$path = str_replace(',', ' ', $path);
				$path = preg_replace('/([a-zA-Z])([0-9\.\-])/', '$1 $2', $path); 
				$path = preg_replace('/([0-9\.])([a-zA-Z])/', '$1 $2', $path);
				$path = preg_replace('/[\s]+/', ' ', trim($path));
				$path = preg_replace('/ ([a-z]{2})/', '$1', $path); 
				
				// decoupage et nettoyage
				$path = explode(' ', $path);
				foreach($path as $k => $v)
				{
					$path[$k] = trim($v);
					if ($path[$k]==='') unset($path[$k]);
				}
				$path = array_values($path);

				// conversion des unites
				$actions = array();
				$action = array();
				for($k=0; $k<count($path); $k+=count($action))
				{
					$action = array();
					$action[] = $path[$k];
					switch($path[$k])
					{
						case 'C':
						case 'c':
							$action[] = $this->style->ConvertToMM($path[$k+1], $this->isInDraw['w']);	// x1
							$action[] = $this->style->ConvertToMM($path[$k+2], $this->isInDraw['h']);	// y1
							$action[] = $this->style->ConvertToMM($path[$k+3], $this->isInDraw['w']);	// x2
							$action[] = $this->style->ConvertToMM($path[$k+4], $this->isInDraw['h']);	// y2
							$action[] = $this->style->ConvertToMM($path[$k+5], $this->isInDraw['w']);	// x
							$action[] = $this->style->ConvertToMM($path[$k+6], $this->isInDraw['h']);	// y
							break;
							
						case 'Q':
						case 'S':
						case 'q':
						case 's':
							$action[] = $this->style->ConvertToMM($path[$k+1], $this->isInDraw['w']);	// x2
							$action[] = $this->style->ConvertToMM($path[$k+2], $this->isInDraw['h']);	// y2
							$action[] = $this->style->ConvertToMM($path[$k+3], $this->isInDraw['w']);	// x
							$action[] = $this->style->ConvertToMM($path[$k+4], $this->isInDraw['h']);	// y
							break;
							
						case 'A':
						case 'a':
							$action[] = $this->style->ConvertToMM($path[$k+1], $this->isInDraw['w']);	// rx
							$action[] = $this->style->ConvertToMM($path[$k+2], $this->isInDraw['h']);	// ry
							$action[] = 1.*$path[$k+3];													// angle de deviation de l'axe X
							$action[] = ($path[$k+4]=='1') ? 1 : 0;										// large-arc-flag 
							$action[] = ($path[$k+5]=='1') ? 1 : 0; 									// sweep-flag
							$action[] = $this->style->ConvertToMM($path[$k+6], $this->isInDraw['w']);	// x
							$action[] = $this->style->ConvertToMM($path[$k+7], $this->isInDraw['h']);	// y
							break;
							
						case 'M':
						case 'L':
						case 'T':
						case 'm':
						case 'l':
						case 't':
							$action[] = $this->style->ConvertToMM($path[$k+1], $this->isInDraw['w']);	// x
							$action[] = $this->style->ConvertToMM($path[$k+2], $this->isInDraw['h']);	// y
							break;
							
						case 'H':
						case 'h':
							$action[] = $this->style->ConvertToMM($path[$k+1], $this->isInDraw['w']);	// x
							break;
							
						case 'V':
						case 'v':
							$action[] = $this->style->ConvertToMM($path[$k+1], $this->isInDraw['h']);	// y
							break;
							
						case 'z':
						case 'Z':
						default:
							break;
					}
					$actions[] = $action;
				}
				
				// on trace
				$this->pdf->svgPolygone($actions, $style);
			}
			
			$this->pdf->undoTransform();
			$this->style->load();
		}
		
		/**
		* balise : G
		* mode : OUVERTURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function o_G($param)
		{
			if (!$this->isInDraw) throw new HTML2PDF_exception(8, 'LINE');
			$this->pdf->doTransform(isset($param['transform']) ? $this->_prepareTransform($param['transform']) : null);
			$this->style->save();
	 		$styles = $this->style->getSvgStyle('path', $param);
			$style = $this->pdf->svgSetStyle($styles);
		}
		
		/**
		* balise : G
		* mode : FERMETURE
		* 
		* @param	array	param�tres de l'�l�ment de parsing
		* @return	null
		*/	
		protected function c_G($param)
		{
			$this->pdf->undoTransform();
			$this->style->load();
		}
		
		protected function _prepareTransform($transform)
		{
			if (!$transform) return null;
			$actions = array();
			if (!preg_match_all('/([a-z]+)\(([^\)]*)\)/isU', $transform, $match)) return null;

			for($k=0; $k<count($match[0]); $k++)
			{
				$nom = strtolower($match[1][$k]);
				$val = explode(',', trim($match[2][$k]));
				foreach($val as $i => $j)
					$val[$i] = trim($j);	

				switch($nom)
				{
					case 'scale':
						if (!isset($val[0])) $val[0] = 1.;		else $val[0] = 1.*$val[0];
						if (!isset($val[1])) $val[1] = $val[0];	else $val[1] = 1.*$val[1];
						$actions[] = array($val[0],0,0,$val[1],0,0);
						break;	
					
					case 'translate':
						if (!isset($val[0])) $val[0] = 0.; else $val[0] = $this->style->ConvertToMM($val[0], $this->isInDraw['w']);
						if (!isset($val[1])) $val[1] = 0.; else $val[1] = $this->style->ConvertToMM($val[1], $this->isInDraw['h']);
						$actions[] = array(1,0,0,1,$val[0],$val[1]);
						break;

					case 'rotate':
						if (!isset($val[0])) $val[0] = 0.; else $val[0] = $val[0]*M_PI/180.;
						if (!isset($val[1])) $val[1] = 0.; else $val[1] = $this->style->ConvertToMM($val[1], $this->isInDraw['w']);
						if (!isset($val[2])) $val[2] = 0.; else $val[2] = $this->style->ConvertToMM($val[2], $this->isInDraw['h']);
						if ($val[1] || $val[2]) $actions[] = array(1,0,0,1,-$val[1],-$val[2]);
						$actions[] = array(cos($val[0]),sin($val[0]),-sin($val[0]),cos($val[0]),0,0);
						if ($val[1] || $val[2]) $actions[] = array(1,0,0,1,$val[1],$val[2]);
						break;
						
					case 'skewx':
						if (!isset($val[0])) $val[0] = 0.; else $val[0] = $val[0]*M_PI/180.;
						$actions[] = array(1,0,tan($val[0]),1,0,0);
						break;
						
					case 'skewy':
						if (!isset($val[0])) $val[0] = 0.; else $val[0] = $val[0]*M_PI/180.;
						$actions[] = array(1,tan($val[0]),0,1,0,0);
						break;
					case 'matrix':
						if (!isset($val[0])) $val[0] = 0.; else $val[0] = $val[0]*1.;
						if (!isset($val[1])) $val[1] = 0.; else $val[1] = $val[1]*1.;
						if (!isset($val[2])) $val[2] = 0.; else $val[2] = $val[2]*1.;
						if (!isset($val[3])) $val[3] = 0.; else $val[3] = $val[3]*1.;
						if (!isset($val[4])) $val[4] = 0.; else $val[4] = $this->style->ConvertToMM($val[4], $this->isInDraw['w']);
						if (!isset($val[5])) $val[5] = 0.; else $val[5] = $this->style->ConvertToMM($val[5], $this->isInDraw['h']);
						$actions[] =$val;
						break;
				}
			}

			if (!$actions) return null;
			$m = $actions[0]; unset($actions[0]);
			foreach($actions as $n)
			{
				$m = array(
					$m[0]*$n[0]+$m[2]*$n[1],
					$m[1]*$n[0]+$m[3]*$n[1],
					$m[0]*$n[2]+$m[2]*$n[3],
					$m[1]*$n[2]+$m[3]*$n[3],
					$m[0]*$n[4]+$m[2]*$n[5]+$m[4],
					$m[1]*$n[4]+$m[3]*$n[5]+$m[5]
				);	
			}
		
			return $m;
		}
		
		protected function _getDrawNumber(&$lst, $key, $n=1, $correct=false)
		{
			$res = array_fill(0, $n, 0);
			$tmp = isset($lst[$key]) ? $lst[$key] : null;
			if (!$tmp) return $res;
			$tmp = explode(' ', trim(preg_replace('/[\s]+/', ' ', $tmp)));
			foreach($tmp as $k => $v)
			{
				$v = trim($v);
				if (!$correct)
				{
					$res[$k] = $this->style->ConvertToMM($v);
				}
				else
				{
					$res[$k] = $this->style->ConvertToMM($v, ($k%2) ? $this->isInDraw['h'] : $this->isInDraw['w']);
				}
			}
			return $res;
		}
		
		/**
		* permet d'afficher un index automatique utilisant les bookmark
		* 
		* @param	string	titre du sommaire
		* @param	int		taille en mm de la fonte du titre du sommaire
		* @param	int		taille en mm de la fonte du texte du sommaire
		* @param	boolean	ajouter un bookmark sp�cifique pour l'index, juste avant le d�but de celui-ci
		* @param	boolean	afficher les num�ros de page associ�s � chaque bookmark
		* @param	int		si pr�sent : page o� afficher le sommaire. sinon : nouvelle page
		* @param	string	nom de la fonte � utiliser
		* @return	null
		*/	
		public function createIndex($titre = 'Index', $size_title = 20, $size_bookmark = 15, $bookmark_title = true, $display_page = true, $on_page = null, $font_name = 'helvetica')
		{
			$old_page = $this->INDEX_NewPage($on_page);
			$this->pdf->createIndex($this, $titre, $size_title, $size_bookmark, $bookmark_title, $display_page, $on_page, $font_name);				
			if ($old_page) $this->pdf->setPage($old_page);	
		}
			
		/**
		* nouvelle page pour l'index. ne pas utiliser directement. seul MyPDF doit l'utiliser !!!!
		* 
		* @param	int		page courante
		* @return	null
		*/	
		public function INDEX_NewPage(&$page)
		{
			if ($page)
			{
				$old_page = $this->pdf->getPage();
				$this->pdf->setPage($page);
				$this->pdf->setXY($this->margeLeft, $this->margeTop);
				$this->maxH = 0;
				$page++;
				return $old_page;
			}
			else
			{
				$this->setNewPage();
				return null;
			}
		}
		
		/**
		* chargement du fichier de langue
		* 
		* @param	string langue
		* @return	null
		*/
		static protected function textLOAD($langue)
		{
			if (count(HTML2PDF::$TEXTES)) return true;
			
			if (!preg_match('/^([a-z0-9]+)$/isU', $langue))
			{
				echo 'ERROR : language code <b>'.$langue.'</b> incorrect.';
				exit;
			}
			
			$file = dirname(__FILE__).'/langues/'.strtolower($langue).'.txt';
			if (!is_file($file))
			{
				echo 'ERROR : language code <b>'.$langue.'</b> unknown.<br>';
				echo 'You can create the translation file <b>'.$file.'</b> and send it to me in order to integrate it into a future version.';
				exit;				
			}
			
			$texte = array();
			$infos = file($file);
			foreach($infos as $val)
			{
				$val = trim($val);
				$val = explode("\t", $val);
				if (count($val)<2) continue;
				
				$t_k = trim($val[0]); unset($val[0]);
				$t_v = trim(implode(' ', $val));
				if ($t_k && $t_v) $texte[$t_k] = $t_v;
			}
			HTML2PDF::$TEXTES = $texte;
			
			return true;
		}
		
		/**
		* recuperer un texte precis
		* 
		* @param	string code du texte
		* @return	null
		*/
		static public function textGET($key)
		{
			if (!isset(HTML2PDF::$TEXTES[$key])) return '######';
			
			return HTML2PDF::$TEXTES[$key];
		}
	}
	
	class HTML2PDF_exception extends exception
	{
		protected $tag = null;
		protected $html = null;
		protected $image = null;
		protected $message_html = '';
		
		/**
		* generer une erreur HTML2PDF
		* 
		* @param	int		numero de l'erreur
		* @param	mixed	indications suplementaires sur l'erreur
		* @return	string	code HTML eventuel associ� � l'erreur
		*/
		final public function __construct($err = 0, $other = null, $html = '')
		{
			// creation du message d'erreur
			$msg = '';
			
			switch($err)
			{
				case 1:
					$msg = (HTML2PDF::textGET('err01'));
					$msg = str_replace('[[OTHER]]', $other, $msg);
					$this->tag = $other; 
					break;
					
				case 2:
					$msg = (HTML2PDF::textGET('err02'));
					$msg = str_replace('[[OTHER_0]]', $other[0], $msg); 
					$msg = str_replace('[[OTHER_1]]', $other[1], $msg); 
					$msg = str_replace('[[OTHER_2]]', $other[2], $msg); 
					break;
					
				case 3:
					$msg = (HTML2PDF::textGET('err03'));
					$msg = str_replace('[[OTHER]]', $other, $msg); 
					$this->tag = $other; 
					break;
					
				case 4:
					$msg = (HTML2PDF::textGET('err04'));
					$msg = str_replace('[[OTHER]]', print_r($other, true), $msg);
					break;
					
				case 5:
					$msg = (HTML2PDF::textGET('err05'));
					$msg = str_replace('[[OTHER]]', print_r($other, true), $msg); 
					break;
					
				case 6:
					$msg = (HTML2PDF::textGET('err06'));
					$msg = str_replace('[[OTHER]]', $other, $msg);
					$this->image = $other;
					break;	
					
				case 7:
					$msg = (HTML2PDF::textGET('err07'));
					break;	
					
				case 8:
					$msg = (HTML2PDF::textGET('err08'));
					$msg = str_replace('[[OTHER]]', $other, $msg); 
					$this->tag = $other; 
					break;
					
				case 9:
					$msg = (HTML2PDF::textGET('err09'));
					$msg = str_replace('[[OTHER_0]]', $other[0], $msg); 
					$msg = str_replace('[[OTHER_1]]', $other[1], $msg); 
					$this->tag = $other[0]; 
					break;
			}
			
			// creation du message HTML
			$this->message_html = '<span style="color: #AA0000; font-weight: bold;">'.(HTML2PDF::textGET('txt01')).$err.'</span><br>';
			$this->message_html.= (HTML2PDF::textGET('txt02')).' '.$this->file.'<br>';
			$this->message_html.= (HTML2PDF::textGET('txt03')).' '.$this->line.'<br>';
			$this->message_html.= '<br>';
			$this->message_html.= $msg;
			
			// creation du message classique
			$msg = HTML2PDF::textGET('txt01').$err.' : '.strip_tags($msg);

			if ($html)
			{
				$this->message_html.= "<br><br>HTML : ...".trim(htmlentities($html)).'...';
				$this->html = $html;
				$msg.= ' HTML : ...'.trim($html).'...';
			}
			
			parent::__construct($msg, $err);
		}
		
		public function __toString()	{ return $this->message_html; }
		public function getTAG()		{ return $this->tag; }
		public function getHTML()		{ return $this->html; }
		public function getIMAGE()		{ return $this->image; }
	}
}
