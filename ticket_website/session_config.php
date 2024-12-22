<?php
// إعدادات ملفات تعريف الارتباط للجلسات
ini_set('session.cookie_httponly', 1); // منع الوصول إلى ملفات تعريف الارتباط عبر JavaScript
ini_set('session.cookie_secure', 1); // استخدام ملفات تعريف الارتباط الآمنة (HTTPS فقط)
ini_set('session.use_only_cookies', 1); // استخدام ملفات تعريف الارتباط فقط للجلسات
ini_set('session.cookie_lifetime', 0); // تعطيل الجلسات بعد غلق المتصفح

?>