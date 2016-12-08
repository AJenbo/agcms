<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<style type="text/css">
* {
    font-family:"Times New Roman", Times, serif;
    font-size:13px;
}
h1 {
    color:#333333;
    font-family:"Times New Roman",Times,serif;
    font-size:17px;
    font-weight:bold;
    margin:0;
}
body {
    background-color:#FFFFFF;
}
</style>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo xhtmlEsc(self::$activeRequirement->getTitle()); ?></title>
</head>

<body><h1><?php
echo xhtmlEsc(self::$activeRequirement->getTitle());
?></h1><div id="text"><?php
echo self::$activeRequirement->getHtml();
?></div></body>
</html>
