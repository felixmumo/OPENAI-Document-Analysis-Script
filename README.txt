Y.A.K AI SUMMARISER — XAMPP STARTER

IMPORTANT SECURITY
An API key was exposed in a chat. Revoke that key in the OpenAI API dashboard immediately and create a replacement. Do not reuse the exposed key.

INSTALL
1. Extract this folder into C:\xampp\htdocs\yak-ai\
2. Copy config.php.example and rename the copy to config.php.
3. Open config.php in Notepad and replace the placeholder with your NEW API key.
4. Start Apache in XAMPP.
5. Ensure PHP cURL is enabled in C:\xampp\php\php.ini (extension=curl), then restart Apache.
6. Open http://localhost/yak-ai/

FILES
- index.html: user interface
- summarize.php: PHP backend calling OpenAI Responses API
- config.php.example: configuration template
- .htaccess: blocks web access to config files

NOTES
- MySQL is not required for this initial test.
- This starter accepts pasted text and TXT/MD/CSV files.
- PDF/DOCX extraction, long-document chunking, login protection, rate limiting and database history are not included.
- Keep config.php private and outside public source control.
