<?php
/* 
List User Usermanager and actual profile active
*/
require('routeros_api.class.php');
$API = new RouterosAPI();
$API->debug = false;
$sitio = 'Name Site';
if ($API->connect('host', 'user', 'pass', 'port_api')) {
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="favicon.ico">

    <title><?php echo $sitio ?></title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/pricing.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="vendor/datatables/dataTables.bootstrap4.css">
    <link rel="stylesheet" type="text/css" href="vendor/datatables/buttons.dataTables.min.css">
  </head>

  <body>

    <div class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom box-shadow">
      <h5 class="my-0 mr-md-auto font-weight-normal">WiFiColombia</h5>
      <nav class="my-2 my-md-0 mr-md-3">
      </nav>
      <a class="btn btn-outline-primary" href="#">Login</a>
    </div>

    <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
      <h1 class="display-4">Usermanager users online with plan <?php echo $sitio ?></h1>
      <p class="lead"></p>
    </div>

    <div class="container">
      <table class="table table-striped table-sm" id="table">
        <thead>
          <tr>
            <th>User</th>
            <th>MAC</th>
            <th>Profile</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $API->write('/ppp/active/getall');
          $READ = $API->read(false);
          $ARRAY = $API->parseResponse($READ);
          if(!empty($ARRAY)){
            foreach($ARRAY as $attrib){
              $arrID=$API->comm("/tool/user-manager/user/getall", 
                array(
                  ".proplist"=> "actual-profile",
                  "?username" => $attrib["name"],
                )
              );
              if(!isset($arrID[0]["actual-profile"])){
                $profile = "N/A";
              }else{
                $profile = $arrID[0]["actual-profile"];
              }
              $usuario = $attrib['name'];
              $mac = $attrib['caller-id'];
              echo '<tr><td>'.$usuario.'</td><td>'.$mac.'</td><td>'.$profile.'</td></tr>';
            }
          }else{
            echo 'No hay datos';
          }
          ?>
        </tbody>
      </table>
    </div>
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.js"></script>
    <script src="vendor/datatables/dataTables.buttons.min.js"></script>
    <script src="vendor/datatables/buttons.html5.min.js"></script>
    <script src="vendor/datatables/pdfmake.min.js"></script>
    <script src="vendor/datatables/vfs_fonts.js"></script>
    <script src="vendor/datatables/jszip.min.js"></script>
    <script src="vendor/datatables/buttons.colVis.min.js"></script>
    <script type="text/javascript">
      $('Document').ready(function(){
        function Table(id,infotext){
          $(id).DataTable({
            "aaSorting": [],
        		"responsive": true,
        		"dom": 'Blfrtip',
        		"bSort": true,
        		"iDisplayLength": 50,
            buttons: [
              {
                extend: 'excelHtml5',
                text: 'E<u>x</u>cel',
                exportOptions: {
                  columns: [0, ':visible']
                },
                key: {
                  key: 'x',
                  altKey: true
                }
              },
              {
                extend: 'pdfHtml5',
                text: '<u>P</u>DF',
                messageTop: infotext,
                exportOptions: {
                  columns: [0, ':visible']
                },
                key: {
                  key: 'p',
                  altKey: true
                }
              },
              {
                extend: 'copyHtml5',
                text: '<u>C</u>opiar',
                exportOptions: {
                  columns: [0, ':visible']
                },
                key: {
                  key: 'c',
                  altKey: true
                }
              },
              {
                extend: 'colvis',
                text: 'Visibilidad'
              }
            ]
          });
          $('.buttons-colvis').click(function(){
            $('.buttons-columnVisibility').removeClass('dt-button').addClass('btn btn-info');
          });
          $('.dt-button').addClass('btn btn-info');
          $('.dt-button').removeClass('dt-button');
          $('.buttons-colvis').click(function(){
            $('.buttons-columnVisibility').addClass('btn btn-block');
          });
        }
        Table('#table','');
        $('.dataTables_length').hide();
      });
    </script>
  </body>
</html>
<?php
}else
{ echo "Don't Connect"; } ?>
