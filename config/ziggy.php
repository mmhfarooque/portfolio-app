<?php

// Route names sent to the browser with every page (@routes and the shared
// `ziggy` prop). The private resume routes are left out so the page is not
// advertised in every page's HTML.
return [
    'except' => ['resume.*'],
];
