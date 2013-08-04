<?php
/********************************************************************************************************************************
*
* LabVIEW Hacker Download Button Plugin
*
* Written By Sammy_K
* www.labviewhacker.com
*
/*******************************************************************************************************************************/
  
 
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();
 
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

//Include LVH Plugin Common Code
if(!defined('LVH_COMMON'))
{
	define('LVH_COMMON', 'lib/plugins/lvhplugincommon.php');
	include 'lib/plugins/lvhplugincommon.php'; 
}
 
/********************************************************************************************************************************
* All DokuWiki plugins to extend the parser/rendering mechanism
* need to inherit from this class
********************************************************************************************************************************/
class syntax_plugin_lvhpacket extends DokuWiki_Syntax_Plugin 
{

	//Return Plugin Info
	function getInfo() 
	{
        return array('author' => 'Sammy_K',
                     'email'  => 'sammyk.labviewhacker@gmail.com',
                     'date'   => '2013-07-28',
                     'name'   => 'LabVIEW Hacker Packet',
                     'desc'   => 'Template for describing a packet',
                     'url'    => 'www.labviewhacker.com');
    }
	

	//Set This To True To Enable Debug Strings
	protected $lvhDebug = false;
	
	/***************************************************************************************************************************
	* Plugin Variables
	***************************************************************************************************************************/
	protected $name = '';
	protected $description = '';		
	protected $size = '';	
	protected $format = '';	
	protected $subPackets = array();	
	protected $allPackets = array();
	
    /********************************************************************************************************************************************
	** Plugin Configuration
	********************************************************************************************************************************************/			
				
    function getType() { return 'protected'; }
    function getSort() { return 32; }
  
    function connectTo($mode) {
        $this->Lexer->addEntryPattern('{{lvh_packet.*?(?=.*?}})',$mode,'plugin_lvhpacket');
		
		//Add Internal Pattern Match For Product Page Elements	
		$this->Lexer->addPattern('\|.*?(?=.*?)\n','plugin_lvhpacket');
    }
	
    function postConnect() {
      $this->Lexer->addExitPattern('}}','plugin_lvhpacket');
    }
	 
	/********************************************************************************************************************************************
	** Handle
	********************************************************************************************************************************************/			
				
    function handle($match, $state, $pos, &$handler) 
	{			
		switch ($state) 
		{		
			case DOKU_LEXER_ENTER :
				break;
			case DOKU_LEXER_MATCHED :					
				//Find The Token And Value (Before '=' remove white space, convert to lower case).
				$tokenDiv = strpos($match, '=');								//Find Token Value Divider ('=')
				$token = strtolower(trim(substr($match, 1, ($tokenDiv - 1))));	//Everything Before '=', Remove White Space, Convert To Lower Case
				$value = substr($match, ($tokenDiv + 1));						//Everything after '='
				switch($token)
				{
					case 'name':						
						$this->name = $value;
						break;	
					case 'description':						
						$this->description = $value;
						break;	
					case 'size':						
						$this->size = $value;
						break;	
					case 'format':						
						$this->format = $value;
						break;
					case (preg_match('/subpacketid[0-9]*/', $token, $pmSubpacketHeader)? true : false ) :						
						foreach($pmSubpacketHeader as $iVal)
						{
							$subPacketHeaderNum = substr($iVal, 11);		//Get Number At End Of String
							$this->subPackets[$subPacketHeaderNum][0] = $value;							
						}
						break;
					case (preg_match('/subpacketsize[0-9]*/', $token, $pmSubPacketSize)? true : false ) :
						foreach($pmSubPacketSize as $iVal)
						{
							$subPacketSizeNum = substr($iVal, 13);		//Get Number At End Of String
							//If Packet Header Has No Data Insert Empty Element For Header
							if(count($this->subPackets[$subPacketSizeNum]) == 0)
							{
								$this->subPackets[$subPacketSizeNum][0] = '';
							}
							$this->subPackets[$subPacketSizeNum][1] = $value;
						}
						break;
					case (preg_match('/subpacketdetails[0-9]*/', $token, $pmSubPacketDetails)? true : false ) :
						foreach($pmSubPacketDetails as $iVal)
						{
							$subPacketDetailsNum = substr($iVal, 16);		//Get Number At End Of String
							//If Packet Header Has No Data Insert Empty Element For Header
							if(count($this->subPackets[$subPacketDetailsNum]) == 0)
							{
								$this->subPackets[$subPacketDetailsNum][0] = '';
							}
							$this->subPackets[$subPacketDetailsNum][2] = $value;
						}
						//$this->detailedText[] = $value;
						break;
						
					default:
						break;
				}
				return array($state, $value);
				break;
			case DOKU_LEXER_UNMATCHED :
				break;
			case DOKU_LEXER_EXIT :
			
				/********************************************************************************************************************************************
				** Build Subpacket Details
				********************************************************************************************************************************************/				
				$details = '';
				$packetSize = 0;
				
				//Count Packet Size
				foreach($this->subPackets as $subPacketVal)
				{
					$packetSize += $subPacketVal[1];
				}
				
				//Calculate Num Cols
				$numCols = ($packetSize)*8;	
				
				//Build Packet Breakdown HTML
				foreach($this->subPackets as $subPacketVal)
				{
					$details .= "				
					   <tr>
						  <td class='subPacketHeaderCell'>
							 " . $subPacketVal[0] . "
						  </td>
						  <td class='subPacketDetailsCell' colspan='32'>
							 " . $subPacketVal[2] . "
						  </td>
					   </tr>";					 
				}
				/************************************************************
				 * Helper Functions For HTML Generation
				 *************************************************************/
					 
				//Convert Packet Size From Bits To Bytes
				$partialByte = 0;
				if( ($packetSize % 8) > 0)
				{
					$partialByte = 1;
				}				
				$packetNumBytes = (floor($packetSize / 8) + $partialByte);  //Number of full bytes needed to hold the entire packet.
				
				/********************************************************
				* Name Row
				*********************************************************/
				$nameRow = "<tr>								
								<td class='packetNameCell' colspan='33'>
									<center>" . $this->name . "</center>
								</td>
							</tr>";
							
				/********************************************************
				* Description Row
				*********************************************************/
				$descriptionRow =  "<tr>
										<td class='subPacketHeaderCell'>
											Description
										</td>
										<td class='packetDescriptionCell' colspan='32'>
											" . $this->description . "
										</td>
									</tr>";
				/********************************************************
				* Size Row
				*********************************************************/
				$sizeRow =  "	<tr>
									<td class='subPacketHeaderCell'>
										Size
									</td>									
									<td class='packetSizeCell' colspan='32'>
										" . $this->size . "
									</td>
								</tr>";
							
				/********************************************************
				* Format Rows
				*********************************************************/
				$numFormatRows = 0;
				//Calculate Number Of Rows Needed For Format
				if( ($packetNumBytes % 4) > 0)
				{
					$numFormatRows = (floor($packetNumBytes / 4)) + 1;
				}
				else
				{
					$numFormatRows = floor($packetNumBytes / 4);
				}
				
				//Build Each Packet Format Row
				$formatRows = "";
				$partialBitsUsed = 0;
				$idNum = 0;
				for($i=0; $i<$numFormatRows; $i++)
				{
					//Build It Backwards
					
					//Build ID Row
					$idBitsRemaining = 32;					
					$loopCountSafty = 0;
					
					//Close ID Row
					$formatRows = "</tr>" . $formatRows;
					
					//TODO - Add Bits and Bytes and padding at the end (right now we probably read off the end of the array when looking for sub packet sizes ans ids
					
					//Add Format ID Row
					while($idBitsRemaining > 0)
					{
						//If There Are More IDs To Add Do So
						if($this->subPackets[$idNum][1] != '')
						{
							//Check If There Is Room For The Entire ID On This Format Row
							$idBitsNeeded = ($this->subPackets[$idNum][1]-$partialBitsUsed);	//Number Of Bit Cols Needed For Next ID Or Partial ID
							
							if( $idBitsNeeded <= $idBitsRemaining )
							{
								
								//Add Entire ID To Current Row
								$formatRows = "<td class='packetIdCell' colspan='" . $idBitsNeeded . "'><center>" . $this->subPackets[$idNum][0] . "</center></td>" . $formatRows;
								$idNum++;
								$partialBitsUsed = 0;
								$idBitsRemaining = $idBitsRemaining - $idBitsNeeded;							
							}
							
							else
							{
								//Add Partial ID To Current Row
								$formatRows = "<td class='packetIdCell' colspan='" . $idBitsRemaining . "'><center>" . $this->subPackets[$idNum][0] . "</center></td></tr><tr>" . $formatRows;
								$partialBitsUsed = $idBitsRemaining;
								$idBitsRemaining = 0;
							}
						}
						else
						{
							//All IDs Have Been Added.  Pad The Rest Of The Row
							$formatRows = "<td class='packetIdCell' colspan='" . $idBitsRemaining . "'><center>" . "PADDING TEST" . "</center></td></tr><tr>" . $formatRows;
						}
						
						//Prevent This Loop From Grinding Forever
						if($loopCountSafty > 256)
						{
							break;
						}
					}
					//Close Bit Row, Open ID Row
					$formatRows = "</tr><tr>" . $formatRows;			
					
					//Add Format Bit Row					
					for($j=8; $j<40; $j++)
					{
						$formatRows = "<td class='packetBitCell'><center>" . ($j % 8) . "</center></td>" . $formatRows;		
					}					
					
					//Close Byte Row And Open Bits Row 
					$formatRows = "</tr><tr>" . $formatRows;
					
					//Add Format Byte Row
					for($j=0; $j<4; $j++)
					{
						$formatRows = "<td class='packetByteCell' colspan='8'><center>" . ($j + ($i*4) ) . "</center></td>" . $formatRows;		
					}						
				}
				
				//Prepend Format Row Header
				$formatRows = "<tr class='packetFormatRow'><td class='subPacketHeaderCell' rowspan='" . ($numFormatRows * 3) . "'>Format</td>" . $formatRows;
				
				//Build TOC
				
				//Build Array To Send To Renderer
				$retVal = array($state, $this->name, $nameRow, $descriptionRow, $sizeRow, $formatRows, $details, $numCols);
				
				//Clear Variables That Will Be Resused Here If Neccissary
				$this->name = '';
				$this->description = '';
				$this->size = '';
				$this->format = '';
				
				return $retVal;
				break;
			case DOKU_LEXER_SPECIAL :
				break;
		}			
		return array($state, $match);
    }
 
	/********************************************************************************************************************************************
	** Render
	********************************************************************************************************************************************/
	
    function render($mode, &$renderer, $data) 
	{
    // $data is what the function handle return'ed.
        if($mode == 'xhtml')
		{
			switch ($data[0]) 
			{
			  case DOKU_LEXER_ENTER : 
				//Initialize Table	
				if($this->lvhDebug) $renderer->doc .= 'ENTER';		//Debug
				
				//$renderer->doc.= '<HTML><body><table border="0">';
				break;
			  case DOKU_LEXER_MATCHED :
				//Add Table Elements Based On Type
				if($this->lvhDebug) $renderer->doc .= 'MATCHED';		//Debug				
				break;
			  case DOKU_LEXER_UNMATCHED :
				//Ignore
				if($this->lvhDebug) $renderer->doc .= 'UNMATCHED';	//Debug
				break;
			  case DOKU_LEXER_EXIT :
				//Close Elements
				if($this->lvhDebug) $renderer->doc .= 'EXIT';		//Debug
				//$renderer->doc.= '</table></body></HTML>';
				
				//Separate Data
				$instPacketName = $data[1];
				$instNameRow = $data[2];
				$instDescriptionRow = $data[3];
				$instSizeRow = $data[4];
				$instFormatRow = $data[5];
				$instDetails = $data[6];
				$instNumCols = $data[7];
				 
				 
				/************************************************************
				* Variables For HTML Generation
				*************************************************************/
				

				//Add Packet Table
				$renderer->doc .= "
					<head>
							<style type='text/css'>

								table.packetTOC
								{  									
									background-color: #FFFFFF;
									border-style:none;	
									border-spacing:0;								
								}
								
								td.packetTOCCell
								{
									border-style:none;		
								}
								
								table.packetTable
								{  
									width:100%;										
									border-style:solid;	
									border-width:2px;
									border-color:#1C1C1C;
									border-collapse:collapse;
									font-size:.9em;
									background-color: #EEEEEE;
								}
								
								tr.packetFormatRow
								{
									
								}
								
								td.packetNameCell
								{ 									
									text-align: center;
									font-size:1.2em;
									font-weight:bold;
									background-color: #A4A4A4;									
									border-bottom-style=solid;
									border-bottom-width:1px;
									border-bottom-color:#1C1C1C;
									border-right-style=solid;
									border-right-width:2px;
									border-right-color:#1C1C1C;
									
								}
								td.packetDescriptionCell
								{ 
									
								}
								
								td.packetSizeCell
								{ 
									border-bottom-style=solid;
									border-bottom-width:1px;
									border-bottom-color:#1C1C1C;
								}
								
								td.packetByteCell
								{ 									
									text-align: center;
									background-color: #A4A4A4;
									font-weight:bold;	
									border-width:1px;
									border-color:#1C1C1C;										
								}
								
								td.packetBitCell
								{ 									
									text-align: center;
									background-color: #BBBBBB;
									font-weight:bold;	
									border-width:1px;
									border-color:#1C1C1C;									
								}
								td.packetIdCell
								{ 									
									text-align: center;
									font-weight:bold;		
									background-color: #E6E6E6;
									border-width:1px;
									border-color:#1C1C1C;									
								}
								
								td.subPacketHeaderCell
								{ 
									width:7%;
									background-color: #A4A4A4;
									text-align:right;
									font-weight:bold;
									border-right-style:solid;
									border-right-width:1px;
									border-right-color:#1C1C1C;
								}
								td.subPacketDetailsCell
								{ 
																		
								}									
							</style>								
					</head>
				
				
					<body>	
						<table class = packetTOC>
							<!--TOC-->	
						</table>												
						</table>
						
						<a id='".trim(str_replace(' ', '', $instPacketName))."'></a>
						<table class='packetTable'>
							" . $instNameRow
							  . $instDescriptionRow
							  . $instSizeRow					
							  . $instFormatRow								
							  . $instDetails . "
						</table>
					</body>";

				//Add To Packet TOC
				$renderer->doc = preg_replace("/<!--TOC-->/", "<tr><td class=packetTOCCell><a href='#".trim(str_replace(' ', '', $instPacketName))."'>" . $instPacketName ." </a></tr></td><!--TOC-->", $renderer->doc, 1);					
				
				break;
			  case DOKU_LEXER_SPECIAL :
				//Ignore
				if($this->lvhDebug) $renderer->doc .= 'SPECIAL';		//Debug
				break;
			}			
            return true;
        }
        return false;
    }
}
	