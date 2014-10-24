<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>{site_title} - {page_title}</title>
<link href="themes/{theme}/style.css" rel="stylesheet">
</head>

<body>
  <a href="{url(home)}">home route with default language</a><br>
  <a href="{url(en/home)}">home route with English as route lang parameter</a><br>
  <a href="{url(fr/home)}">home route with Français as route lang parameter</a><br>
  <a href="{url(fr/home)}&lang=en_US">home route with Français as route lang parameter but forced language as English</a><br>
  <a href="{url(en/home)}&lang=fr_FR">home route with Français as route lang parameter but forced language as English</a><br>

  <h1 class="title">{_welcome_text}</h1>

  <p>{_text}

</body>

</html>
