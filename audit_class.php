<?php
function url_exists( $url ) {    
    $file_headers = @get_headers( $url );
    if( $file_headers[0] == 'HTTP/1.1 404 Not Found' ) {
        return false;
    }
    else {
        return true;
    }
}

class Audit {
    
    public function __construct( Sitemap $sitemap ) {
        $this->domain = $sitemap->domain;
        foreach ( $sitemap->urls as $url ) {
            echo "<h2>PAGE: $url</h2>";
            $this->audit_site( $url, new DOMDocument );
        }
    }
    
    private function audit_site( $url, DOMDocument $dom ) {
        @$dom->loadHTMLFile( $url );
        $this->print_titles( $dom->getElementsByTagName('title') );
        $this->print_descriptions( $dom->getElementsByTagName('meta') );
        $this->print_h1s( $dom->getElementsByTagName('h1') );
        $this->print_alts ( $dom->getElementsByTagName('img') );
        unset( $dom );
    }
    
    public function print_titles( $titles ) {
        if ( count( $titles ) > 0 ) {
            echo '<h2>Title(s)</h2>';
            foreach( $titles as $title ) {
                if ( $title->nodeValue != '' ) {
                    echo $title->nodeValue, PHP_EOL;
                } else {
                    echo '<p>There was no page title.</p>';
                }
            }
        }
    }
    
    public function print_descriptions( $descriptions ) {
        if ( count( $descriptions ) > 0 ) {
            echo '<h2>Meta Description(s)</h2>';
            foreach( $descriptions as $description ) {
                if ( $description->getAttribute('name') == 'description' || $description->getAttribute('name') =='Description' ) {
                    if ( $description->getAttribute('content') != '' ) {
                        echo $description->getAttribute('content'), PHP_EOL;
                    } else {
                        echo '<p>There was no meta description.</p>';
                    }
                }
            }
        }
    }
    
    public function print_h1s( $h1s ) {
        if ( count( $h1s ) > 0 ) {
            echo '<h2>H1 Tag(s):</h2>';
            foreach( $h1s as $h1 ) {
                if ( $h1->nodeValue != '' ) {
                    echo $h1->nodeValue . "<br>";
                }
                else {
                    echo "<p>There were no h1 tags.</p>";
                }
            } 
        }
    }
    
    public function print_alts( $imgs ) {
        if ( count( $imgs ) > 0 ) {
            echo '<h2>Alt Text:</h2>';
            foreach( $imgs as $img ) {
                if ( $img->getAttribute('alt') != '' ) {
                    echo '<p>' . $img->getAttribute('src') . ': <strong>' . $img->getAttribute('alt') . "</strong></p>";
                } else {
                    echo '<p>' . $img->getAttribute('src') . ': <strong>Has no alt text.</strong></p>';
                }
            }
        }
    }
}

class Sitemap {
    
    function __construct( $url ) {
        
        $this->sanitize_domain( $url );
        $this->find_sitemap();
        $this->parse_sitemap();
        return $this->urls;
        
    }
    
    private function sanitize_domain( $url ) {
        $secure = preg_match( "/https\:\/\//", $url );
        $pattern = '/([\/]|https?\:\/\/|www\.)/i';
        $replacement = '';
        $domain = preg_replace( $pattern, $replacement, $url );
        $this->domain = $secure ? "https://$domain" : "http://$domain";
    }
    
    private function find_sitemap() {
        $sitemap = $this->domain . '/sitemap.xml';
        if( url_exists( $sitemap ) ) {            
            $this->sitemap = $sitemap;
            echo '<h2>Sitemap was found!</h2>';
            echo url_exists( $this->domain . '/robots.txt' ) ? '<h2>robots.txt was found!</h2>' : '<h2>robots.txt was not found</h2>';
        }
        else {
            try {
                $this->parse_robots();
            } catch ( Exception $e ) {
                echo $e->getMessage();
            }
        }
    }
    
    private function parse_robots() {
        $robots_file = $this->domain . '/robots.txt';
        if ( url_exists( $robots_file ) ) {
            $robots = file_get_contents( $robots_file );
            $pattern = '/(?!((sitemap|Sitemap):\s))[a-zA-Z0-9\:\/\.\-\_]+.xml/';
            if ( preg_match( $pattern, $robots, $matches ) ) {
                $this->sitemap = $matches[0];
                echo '<h2>Sitemap was found!</h2>';
            } else {
                throw new Exception( 'No sitemap location found in: ' . $robots_file );
            }
            echo '<h2>robots.txt was found!</h2>';
        } else {
            throw new Exception( 'No robots file found at: ' . $robots_file );
        }
    }
    
    private function parse_sitemap() {
        
        if ( url_exists( $this->sitemap ) ) {
            $sitemap = simplexml_load_file( $this->sitemap );
            $urls = array();
            for( $i = 0; $i < count( $sitemap->url ); $i++ ){
                array_push( $urls, $sitemap->url[$i]->loc );
            }
            array_unshift( $urls, $this->domain );
            $this->urls = $urls;
        } else {
            throw new Exception( "Sitemap was not found at: " . $this->sitemap );
        }
        
    }
}
?>