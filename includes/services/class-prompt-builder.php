<?php
/**
 * Prompt Builder Service
 *
 * Constructs AI prompts with product catalog and conversation history.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes/services
 */

class Bracefox_BW_Prompt_Builder {

    /**
     * Product repository
     */
    private $product_repo;

    /**
     * Blog repository
     */
    private $blog_repo;

    /**
     * Constructor
     */
    public function __construct() {
        $this->product_repo = new Bracefox_BW_Product_Repository();
        $this->blog_repo = new Bracefox_BW_Blog_Repository();
    }

    /**
     * Build system prompt
     *
     * @param array $user_message User's message for context-based filtering
     * @return string System prompt
     */
    public function build_system_prompt($user_message = '') {
        $products = $this->get_filtered_products($user_message);
        $blogs = $this->get_filtered_blogs($user_message);

        $prompt = $this->get_role_definition();
        $prompt .= $this->get_strict_rules();
        $prompt .= $this->get_output_format();
        $prompt .= $this->format_product_catalog($products);
        $prompt .= $this->format_blog_catalog($blogs);
        $prompt .= $this->get_matching_guidelines();

        return $prompt;
    }

    /**
     * Get role definition
     */
    private function get_role_definition() {
        return <<<EOT
# ROL

Je bent de Bracefox Blessurewijzer, een vriendelijke en professionele AI-assistent gespecialiseerd in blessure advies en product aanbevelingen.

Je helpt bezoekers van de Bracefox webshop bij het vinden van het juiste product voor hun blessure of klacht, en geeft gratis, waardevolle gezondheidsadviezen.


EOT;
    }

    /**
     * Get strict rules
     */
    private function get_strict_rules() {
        return <<<EOT
# STRIKTE REGELS

1. Adviseer ALLEEN producten uit de Bracefox catalogus hieronder
2. Geef ALTIJD gratis herstel tips, ongeacht of iemand iets koopt
3. Stel maximaal 2 verduidelijkende vragen voordat je advies geeft
4. Wees empathisch en begrijpend - mensen delen hun gezondheidsklachten
5. Geef GEEN medische diagnoses - verwijs bij twijfel naar een professional
6. Gebruik Nederlandse taal op B1-niveau (begrijpelijk, zonder jargon)
7. Blijf altijd positief en bemoedigend
8. Bij ernstige symptomen: adviseer direct professionele hulp te zoeken


EOT;
    }

    /**
     * Get output format specification
     */
    private function get_output_format() {
        return <<<EOT
# OUTPUT FORMAT

Je antwoorden MOETEN altijd in het volgende JSON format zijn:

{
  "message_type": "question" | "advice",
  "personal_message": "Persoonlijke, empathische boodschap aan de gebruiker",
  "question": "Verduidelijkende vraag (alleen bij message_type: question)",
  "product_recommendation": {
    "product_id": 123,
    "reasoning": "Waarom dit product past bij de klacht"
  },
  "health_advice": {
    "exercises": [
      {
        "name": "Naam van oefening",
        "description": "Hoe uit te voeren",
        "duration": "30 seconden per kant",
        "frequency": "3x per dag"
      }
    ],
    "thermal_advice": {
      "method": "koelen" | "verwarmen",
      "explanation": "Waarom dit helpt",
      "duration": "15 minuten"
    },
    "rest_advice": "Advies over rust en belasting",
    "lifestyle_tips": ["Tip 1", "Tip 2"]
  },
  "severity_warning": true/false,
  "related_blogs": [123, 456]
}


EOT;
    }

    /**
     * Format product catalog
     */
    private function format_product_catalog($products) {
        $catalog = "# BRACEFOX PRODUCT CATALOGUS\n\n";

        if (empty($products)) {
            $catalog .= "Geen producten beschikbaar.\n\n";
            return $catalog;
        }

        foreach ($products as $product) {
            $catalog .= sprintf(
                "## Product ID: %d\n",
                $product['id']
            );
            $catalog .= sprintf("Naam: %s\n", $product['name']);
            $catalog .= sprintf("Prijs: €%.2f\n", $product['price']);
            $catalog .= sprintf("Categorie: %s\n", implode(', ', $product['categories']));
            $catalog .= sprintf("Beschrijving: %s\n", $product['description']);

            if (!empty($product['features'])) {
                $catalog .= "Kenmerken:\n";
                foreach ($product['features'] as $feature) {
                    $catalog .= "- " . $feature . "\n";
                }
            }

            $catalog .= sprintf("Link: %s\n\n", $product['url']);
        }

        return $catalog;
    }

    /**
     * Format blog catalog
     */
    private function format_blog_catalog($blogs) {
        $catalog = "# BRACEFOX ARTIKELEN\n\n";

        if (empty($blogs)) {
            $catalog .= "Geen artikelen beschikbaar.\n\n";
            return $catalog;
        }

        foreach ($blogs as $blog) {
            $catalog .= sprintf(
                "## Artikel ID: %d\n",
                $blog['id']
            );
            $catalog .= sprintf("Titel: %s\n", $blog['title']);
            $catalog .= sprintf("Onderwerp: %s\n", implode(', ', $blog['categories']));
            $catalog .= sprintf("Samenvatting: %s\n", $blog['excerpt']);
            $catalog .= sprintf("Link: %s\n\n", $blog['url']);
        }

        return $catalog;
    }

    /**
     * Get matching guidelines
     */
    private function get_matching_guidelines() {
        return <<<EOT
# MATCHING RICHTLIJNEN

## Product Selectie
1. Identificeer eerst het lichaamsdeel (knie, enkel, pols, elleboog, rug, etc.)
2. Bepaal het type klacht (acuut/chronisch, lichte/ernstige pijn)
3. Overweeg de context (sport, werk, dagelijks, nacht)
4. Match met producten op basis van:
   - Categorie (primair filter)
   - Ondersteuningsniveau (licht/medium/stevig)
   - Specifieke features (flexibel, verstelbaar, compressie)

## Advies Kwaliteit
1. Oefeningen: Geef 2-4 relevante oefeningen met duidelijke instructies
2. Thermisch: Kies koelen (acute blessure < 48u) of verwarmen (chronisch)
3. Rust: Geef specifiek advies over activiteit aanpassen
4. Lifestyle: Voeg alleen relevante tips toe (houding, werkplek, etc.)

## Ernstige Symptomen
Detecteer keywords zoals: "heel veel pijn", "kan niet lopen", "opgezwollen",
"roodheid", "warmte", "koorts", "naar ziekenhuis"

Bij detectie: severity_warning = true en adviseer professionele hulp.


EOT;
    }

    /**
     * Get filtered products based on user message
     * Implements token optimization by filtering relevant products
     */
    private function get_filtered_products($user_message) {
        $all_products = $this->product_repo->get_all_products();

        if (empty($user_message)) {
            // Return all products if no context
            return array_slice($all_products, 0, 20); // Max 20 for token limit
        }

        // Simple keyword-based filtering
        $keywords = $this->extract_keywords($user_message);
        $filtered = array();

        foreach ($all_products as $product) {
            $relevance_score = 0;

            // Check if any keyword matches product name, categories, or description
            foreach ($keywords as $keyword) {
                $search_text = strtolower(
                    $product['name'] . ' ' .
                    implode(' ', $product['categories']) . ' ' .
                    $product['description']
                );

                if (strpos($search_text, $keyword) !== false) {
                    $relevance_score++;
                }
            }

            if ($relevance_score > 0) {
                $product['relevance'] = $relevance_score;
                $filtered[] = $product;
            }
        }

        // Sort by relevance
        usort($filtered, function($a, $b) {
            return $b['relevance'] - $a['relevance'];
        });

        // Return top 20 most relevant
        return array_slice($filtered, 0, 20);
    }

    /**
     * Get filtered blogs based on user message
     */
    private function get_filtered_blogs($user_message) {
        $all_blogs = $this->blog_repo->get_all_blogs();

        if (empty($user_message)) {
            return array_slice($all_blogs, 0, 5);
        }

        $keywords = $this->extract_keywords($user_message);
        $filtered = array();

        foreach ($all_blogs as $blog) {
            $relevance_score = 0;

            foreach ($keywords as $keyword) {
                $search_text = strtolower(
                    $blog['title'] . ' ' .
                    implode(' ', $blog['categories']) . ' ' .
                    $blog['excerpt']
                );

                if (strpos($search_text, $keyword) !== false) {
                    $relevance_score++;
                }
            }

            if ($relevance_score > 0) {
                $blog['relevance'] = $relevance_score;
                $filtered[] = $blog;
            }
        }

        usort($filtered, function($a, $b) {
            return $b['relevance'] - $a['relevance'];
        });

        return array_slice($filtered, 0, 5);
    }

    /**
     * Extract keywords from user message
     */
    private function extract_keywords($message) {
        $message = strtolower($message);

        // Common body parts and injury-related keywords
        $keywords_map = array(
            'knie' => array('knie', 'knieen'),
            'enkel' => array('enkel', 'enkels'),
            'pols' => array('pols', 'polsen'),
            'elleboog' => array('elleboog', 'ellebogen'),
            'schouder' => array('schouder', 'schouders'),
            'rug' => array('rug', 'rugpijn', 'rugklachten'),
            'nek' => array('nek', 'nekpijn'),
            'heup' => array('heup', 'heupen'),
            'voet' => array('voet', 'voeten'),
            'hand' => array('hand', 'handen'),
            'duim' => array('duim', 'duimen'),
            'hardlopen' => array('hardlopen', 'rennen', 'joggen'),
            'sport' => array('sport', 'sporten'),
            'tennis' => array('tennis', 'tennissen'),
            'golf' => array('golf', 'golfen'),
            'fitness' => array('fitness', 'gym'),
        );

        $found_keywords = array();

        foreach ($keywords_map as $main_keyword => $variations) {
            foreach ($variations as $variation) {
                if (strpos($message, $variation) !== false) {
                    $found_keywords[] = $main_keyword;
                    break;
                }
            }
        }

        return array_unique($found_keywords);
    }
}
