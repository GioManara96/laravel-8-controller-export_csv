<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Response;

class ExportCSV extends Controller {
    function export() {
        // prendo tutti i percorsi relativi delle immagini per i prodotti pubblicati
        $productsImages = DB::table("dpb_posts as p")
            ->join("dpb_postmeta as pm", function ($join) {
                $join->on("pm.post_id", "=", "p.ID")
                    ->on("pm.meta_key", "=", DB::raw("'_thumbnail_id'"));
            })
            ->join("dpb_postmeta as am", function ($join) {
                $join->on("am.post_id", "=", "pm.meta_value")
                    ->on("am.meta_key", "=", DB::raw("'_wp_attached_file'"));
            })
            ->select("p.ID", "pm.post_id", "am.meta_key", "am.meta_value")
            ->where("p.post_type", "=", DB::raw("'product'"))
            ->where("p.post_status", "=", DB::raw("'publish'"))
            ->get();

        // prendo tutti i prodotti pubblicati presenti in postmeta
        $products = DB::table("dpb_posts as p")
            ->join("dpb_postmeta as pm", "pm.post_id", "=", "p.ID")
            ->select("p.ID", "p.post_title", "pm.post_id", "pm.meta_key", "pm.meta_value")
            ->where("p.post_type", "=", DB::raw("'product'"))
            ->where("p.post_status", "=", DB::raw("'publish'"))
            ->get();

        $toPrint = array(); // array in cui aggiungere i prodotti da salvare
        foreach ($products as $i => $product) {
            // al primo ciclo aggiungo id e titolo all'array dei campi
            if ($i == 0) {
                $fields = array("ID" => $product->ID, "title" => $product->post_title);
            }
            // se il prodotto di questo ciclo è diverso dal precedente, pusho i campi nell'array di stampa e re-inizializzo l'array dei campi
            if ($i > 0 && $products[$i]->ID != $products[$i - 1]->ID) {
                array_push($toPrint, $fields);
                $fields = array("ID" => $product->ID, "title" => $product->post_title, $product->meta_key => $product->meta_value);
            } else {
                // se invece è uguale al precedente allora aggionro i valori dei campi
                $fields[$product->meta_key] = $product->meta_value;
            }
            // all'ultimo giro mi ricordo di pushare nell'array di stampa l'ultimo prodotto
            if (empty($products[$i + 1])) {
                array_push($toPrint, $fields);
            }
        }

        /**
         * visto che per trovare le immagini ho dovuto fare due ricerche diverse ora vado ad aggiungere
         * ad ogni prodotto la sua immagine, completando il percorso relativo che ricavo dalla query
         * con la parte mancante.
         */
        $pathToUploads = "https://giovannimanara.it/woocommerce-tesi/wp-content/uploads/";
        foreach ($productsImages as $j => $image) {
            if ($image->ID == $toPrint[$j]["ID"]) {
                $toPrint[$j][$image->meta_key] = $pathToUploads . $image->meta_value;
            }
        }

        // echo "<pre style='background-color: #000; color: #0f0; padding: 16px;'>";
        // print_r($toPrint);
        // echo "</pre>";

        $headers = array(
            'Content-Type' => 'text/csv'
        );

        if (!file_exists(public_path() . "/products_csv")) {
            mkdir(public_path() . "/products_csv");
        }

        $data = date("d-m-Y_His");
        $filename =  public_path("products_csv/" . $data . ".csv");
        $handle = fopen($filename, 'w');

        foreach ($toPrint as $product) {
            fputcsv($handle, [
                $product["ID"],
                $product["_sku"],
                $product["title"],
                $product["_regular_price"],
                $product["_stock_status"],
                $product["_thumbnail_id"],
                $product["_wp_attached_file"]
            ]);
        }

        fclose($handle);

        //download command
        return Response::download($filename, $data . ".csv", $headers);
    }
}
