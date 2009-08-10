<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Pakke pris</title>
<script type="text/javascript" src="calcpakkepris.js"></script>
<script type="text/javascript" src="javascript.js"></script>
<script type="text/javascript" src="pakkepris.js"></script>
<link href="style.css" rel="stylesheet" type="text/css" />
</head>
<body onload="calc()">
<div id="loading" style="display:none;"><img src="load.gif" width="228" height="144" alt="" title="Komunikere med post danmark..." /></div>
<?php
include('menu.html');
?>
<div style="width:650px; margin:10px 0 0 180px"><img src="http://www.postdanmark.dk/pfs/grafik/pakker.gif" alt="" style="float:right; padding:0 0 5px 0" height="50" />
  <h2 style="padding:25px 0 0 0; margin:0">Pakke pris</h2>
  <hr />
  <label>
  <input style="width:auto;height:auto;" type="radio" name="optRecipType" value="P" onchange="changeOptRecipType()" onclick="changeOptRecipType()" />
  Privat</label>
  <label>
  <input style="width:auto;height:auto;" type="radio" name="optRecipType" value="E" onchange="changeOptRecipType()" onclick="changeOptRecipType()" />
  Erhverv</label>
  <label>
  <input style="width:auto;height:auto;" type="radio" name="optRecipType" value="O" onchange="changeOptRecipType()" onclick="changeOptRecipType()" checked="checked" />
  Postopkrævning</label>
  <table>
    <tbody>
      <tr>
        <td>Standard pakker </td>
        <td><select onchange="standard(this.selectedIndex)" onkeyup="standard(this.selectedIndex)">
            <option value="" selected="selected">Vælg størelse...</option>
            <option>SB6</option>
            <option>SB27</option>
            <option>1,5 meter rør + SB4</option>
            <option>1,5 meter rør + SB5</option>
            <option>1,5 meter rør + SB6</option>
            <option>2 meter rør + SB4</option>
            <option>2 meter rør + SB5</option>
            <option>2 meter rør + SB6</option>
            <option>1.5 meter rør</option>
            <option>2 meter rør</option>
          </select></td>
      </tr>
      <tr>
        <td>Højde </td>
        <td><input id="height" name="height" class="text" onchange="calc()" onkeyup="calc()" style="width:40px;text-align:right;" />
          cm</td>
      </tr>
      <tr>
        <td>Brede </td>
        <td><input id="width" name="width" class="text" onchange="calc()" onkeyup="calc()" style="width:40px;text-align:right;" />
          cm</td>
      </tr>
      <tr>
        <td>Længde </td>
        <td><input id="length" name="length" class="text" onchange="calc()" onkeyup="calc()" style="width:40px;text-align:right;" />
          cm</td>
      </tr>
      <tr>
        <td> Vægt:</td>
        <td><label>
          <input onchange="calc()" onkeyup="calc()" name="weight" id="weight" maxlength="11" style="width:40px;text-align:right;" value="" />
          kg. </label></td>
      </tr>
      <tr>
        <td> Forsigtig:</td>
        <td><input style="width:auto;height:auto;" name="ss1" id="ss1" value="Forsigtig" type="checkbox" onchange="calc()" onkeyup="calc()" /></td>
      </tr>
      <tr id="trVolume">
        <td> Volume:</td>
        <td><input style="width:auto;height:auto;" name="ss2" id="ss2" value="Volume" type="checkbox" onchange="calc()" onkeyup="calc()" /></td>
      </tr>
      <tr id="trExpress">
        <td> Lørdags express:</td>
        <td><input style="width:auto;height:auto;" name="ss46" id="ss46" value="Express" type="checkbox" onchange="calc()" onkeyup="calc()" /></td>
      </tr>
      <tr>
        <td> Værdi:</td>
        <td><label>
          <input name="ss5amount" id="ss5amount" value="000" style="text-align:right;" onchange="calc()" onkeyup="calc()" />
          kr. <br />
          Hvis netto pris overstiger 4600,-</label></td>
      </tr>
    </tbody>
  </table>
  <span style="border-bottom:3px #000000 double;"><span id="porto"></span> DKK</span> </div>
</body>
</html>
