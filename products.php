<?php

require_once __DIR__ . '/private/dataService.php';
require_once __DIR__ . '/private/security.php';

$DOMAIN_NAME = 'romello98.myshopify.com';
$USERNAME = 'a9d2534cf26613ae67f319ec618900db';
$PASSWORD = 'b69933c4444e2f4763d9550af23bddbf';
$APP_VERSION = '2020-01';
$URL = "https://$USERNAME:$PASSWORD@$DOMAIN_NAME/admin/api/$APP_VERSION/products.json";

if(!is_authenticated())
{
    header('Location: login.php');
}

setlocale(LC_TIME, "fr_FR");

$currentUser = getCurrentLoggedUser();
$dataService = new DataService();
$sales = $dataService->getPagedSalesByPartner($currentUser->ID);

$shopifyProducts = file_get_contents($URL);
$products = json_decode($shopifyProducts)->products;

?>

<?php ob_start(); ?>

<h1 class="mb-5">Produits</h1>

<h2 class="mb-5">Générateur de lien partenaire</h2>

<script>
    $(document).ready(function() {
        $('form').submit(false);
        baseURLInput = $('#baseURL');
        partnerURL = $('#trackedURL');
        partnerCode = '<?php echo $currentUser->PartnerCode; ?>';
        buttonCopy = $('#button-copy');
    });

    function toPartnerLink()
    {
        $(baseURLInput).removeClass('is-invalid');
        $(buttonCopy).addClass('disabled');
        try
        {
            let baseURL = baseURLInput.val();
            let url = new URL(baseURL);
            url.searchParams.set('ref', partnerCode);
            partnerURL.val(url);
            $(buttonCopy).removeClass('disabled');
            return;
        } catch(e) 
        { 
            $(baseURLInput).addClass('is-invalid');
        }
    }

    function selectAll(elem)
    {
        elem.setSelectionRange(0, elem.value.length);
        elem.select();
    }

    function copyToClipboard(element) {
        selectAll(partnerURL.get(0));
        document.execCommand("copy");
        $(element).attr("title", "Copié !").tooltip("_fixTitle").tooltip("show").attr("title", "Copier").tooltip("_fixTitle");
    }

    function sendToCopy(elem)
    {
        let a = elem.parentElement.querySelector('.product-link');
        baseURLInput.val(a.href);
        baseURLInput.change();
        copyToClipboard(elem);
    }

    function getPromotionTools(productID, callback = displayTools)
    {
        let request = new XMLHttpRequest();
        let medias = null;
        request.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            medias = JSON.parse(this.responseText);
            callback(medias, productID);
        }
        };
        request.open("GET", "/data-actions.php?action=GET_PROMOTION_TOOLS_BY_PRODUCT_ID&ID=" + productID, true);
        request.send();
    }

    function displayTools(medias, productID)
    {
        let images = medias.filter(media => media.match(/.*\.(png|jpg|jpeg|webm)/));
        let videos = medias.filter(media => !images.includes(media));

        let divContent = document.createElement('div');

        if(videos.length > 0)
        {
            let videosTitle = $($.parseHTML("<h2>Vidéos</h2>")).get(0);
            let row = document.createElement('div');
            row.classList.add('row');

            divContent.appendChild(videosTitle);
            divContent.appendChild(row);

            for(let video of videos)
            {
                let div = document.createElement('div');
                div.classList.add('col-md-12');
                let videoHTML = document.createElement('video');
                videoHTML.src = `/promotion-tools/product-${productID}/${video}`;
                videoHTML.autoplay = 'false';
                videoHTML.controls = 'true';
                videoHTML.classList.add('w-100');
                div.appendChild(videoHTML);
                row.appendChild(div);
            }

            divContent.appendChild(row);
        }

        if(images.length > 0)
        {
            let imagesTitle = $($.parseHTML("<h2>Images</h2>")).get(0);
            let rowImages = document.createElement('div');
            rowImages.classList.add('row');

            divContent.appendChild(imagesTitle);
            divContent.appendChild(rowImages);

            for(let image of images)
            {
                let div = document.createElement('div');
                div.classList.add('col-md-6');
                let imageHTML = document.createElement('img');
                imageHTML.src = `/promotion-tools/product-${productID}/${image}`;
                imageHTML.classList.add('w-100');
                div.appendChild(imageHTML);
                rowImages.appendChild(div);
            }

            divContent.append(rowImages);
        }

        let modalBody = $('#promotion-tools #body .content');
        modalBody.empty();
        modalBody.append(divContent);
        $('#promotion-tools').modal();
    }
</script>
<form class="mb-5">
    <div class="form-group">
        <label for="baseURL">Lien du site</label>
        <input onchange="toPartnerLink()" class="form-control" id="baseURL" name="baseURL" type="text" />
        <small>Collez ici l'URL du produit ou de la page que vous allez partager.</small>
    </div>
    <div class="form-group">
        <label for="trackedURL">Lien partenaire généré</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <button data-toggle="tooltip" title="Copier" id="button-copy" class="btn btn-sm btn-primary" onclick="copyToClipboard(this)">Copier</button>
            </div>
            <input onclick="selectAll(this)" class="form-control" id="trackedURL" name="trackedURL" type="text" readonly aria-readonly="true"/>
        </div>
    </div>
</form>

<h2 class="mb-5">Tous les produits</h2>

<div class="row mb-5">
    <?php foreach($products as $product) : ?>
        <div class="col-md-3">
            <img class="mb-3 product-image" src="<?php echo $product->images[0]->src ?? '/images/products/default.svg'; ?>" />
            <h5 class="mb-3"><a class="d-block product-link" target="_blank" href="<?php echo "https://$DOMAIN_NAME/products/$product->handle"; ?>"><?php echo $product->title; ?></a></h5>
            <a onclick="sendToCopy(this)" href="#trackedURL" class="mb-2 d-block btn btn-primary">Copier lien affilié</a>
            <a class="d-block btn btn-dark text-light" onclick="getPromotionTools(<?php echo $product->id ?>)">Outils de promotion</a>
        </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="promotion-tools" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Outils de promotion</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div id="body" class="modal-body">
        <div class="text-secondary">
            <p class="mb-2 pb-0">Pour sauver un média, faites un clic droit sur l'élement, puis sélectionnez "Enregistrer sous...".</p>
        </div>
        <div class="content">
            
        </div>
    </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

<?php 
    $content = ob_get_clean();
    require_once __DIR__ . '/private/templates/main-template.php'; 
?>