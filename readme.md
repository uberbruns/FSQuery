FSQuery
========

Examples
--------


```php
// INIT
$portfolio = new FSQuery("/Users/kb/Desktop/Portfolio");

// MIME TYPE
$results = $portfolio->query("image/jpeg");

// FILE- OR FOLDERNAME
$results = $portfolio->query("#PDF");

// EXTENSION
$results = $portfolio->query(".gif");

// DESCENDANTS
$results = $portfolio->query("#websites #index.html");

// CHILD
$results = $portfolio->query("#websites > directory > #index.html");

// ROOT ELEMENTS
$results = $portfolio->query("#Portfolio > directory");

// COMBINED QUERIES
$results = $portfolio->query("#Portfolio > directory, #websites #index.html");
```

TIP
---
Don't let this script run hundred times in a second or on every
page request. Get your data from the filesystem, process it and
cache the result. This script is not optimized for speed and/or
efficiency, but for convenience.




The MIT License
---------------

Copyright (c) 2012 Karsten Bruns (karsten{at}bruns{dot}me)

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
