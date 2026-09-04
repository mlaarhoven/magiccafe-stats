<?php
  include ("partial_header.php");
  include ("partial_nav.php");

  $event_id = $_SESSION['event_id'];
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }

  $mode = '';
  $set_id = '';
  if ( isset($_REQUEST['mode']) ) {
    $mode = mysqli_real_escape_string( $cn, $_REQUEST['mode'] );
  }
  if ( isset($_REQUEST['set_id']) ) {
    $set_id = mysqli_real_escape_string( $cn, $_REQUEST['set_id'] );
  }

  $zip_message = '';
  $zip_url = '';
  $zip_error = '';

  if ( $mode == 'create_zip' && $set_id <> '' ) {
    set_time_limit(0);

    if ( !class_exists('ZipArchive') ) {
      $zip_error = 'ZipArchive extension is not available on this server.';
    }

    $sql = "SELECT filehash FROM log_sessionOK WHERE prijs=0 AND filename IS NOT NULL AND set_id = '" . $set_id . "'";
    $result = ( $zip_error == '' ) ? mysqli_query( $cn , $sql ) : false;

    if ( !$result ) {
      if ( $zip_error == '' ) {
        $zip_error = 'Database error while fetching photos.';
      }
    } else {
      $photo_dir = '/home/magicshoot/domains/magicshoot.nl/public_html/photos/' . $event_id;
      if ( !is_dir($photo_dir) ) {
        mkdir($photo_dir, 0755, true);
      }

      $zip_name = hash('sha256', $event_id . '|' . $set_id . '|' . microtime(true) . '|' . bin2hex(random_bytes(8))) . '.zip';
      $zip_path = $photo_dir . '/' . $zip_name;
      $zip_url  = 'https://www.magicshoot.nl/photos/' . $event_id . '/' . $zip_name;

      $zip = new ZipArchive();
      if ( $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true ) {
        $zip_error = 'Unable to create zip file.';
      } else {
        $added = 0;
        $missing = 0;
        $downloaded = 0;

        while ( $row = mysqli_fetch_object($result) ) {
          $filehash = $row->filehash;
          
          if ( $filehash == '' ) {
            continue;
          }

          $photo_path = $photo_dir . '/' . $filehash . '.jpg';
          if ( file_exists($photo_path) ) {
            $zip->addFile($photo_path, $filehash . '.jpg');
            $added++;
            continue;
          }

          $photo_url = 'https://www.magicshoot.nl/photo/' . $event_id . '/' . $filehash . '.jpg';
          $photo_content = @file_get_contents($photo_url);
          if ( $photo_content !== false ) {
            $zip->addFromString($filehash . '.jpg', $photo_content);
            $added++;
            $downloaded++;
          } else {
            $missing++;
          }
        }

        $zip->close();

        if ( $added == 0 ) {
          @unlink($zip_path);
          $zip_error = 'No photos found for this set.';
        } else {
          $zip_message = 'Zip created with ' . $added . ' photos.';
          if ( $missing > 0 ) {
            $zip_message .= ' Missing: ' . $missing . '.';
          }
          if ( $downloaded > 0 ) {
            $zip_message .= ' Downloaded: ' . $downloaded . '.';
          }
        }
      }
    }
  }
?>
    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Foto zip</li>
      </ol>
    </div>
  </header>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">
              <div class="row">
                <div class="col-sm-12 col-md-12 col-lg-12">
                  <div class="card">
                    <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Foto zip downloaden</h2>
                      
                    </div>
                    <div class="card-body">
                      <?php if ( $zip_error <> '' ) { ?>
                        <div class="alert alert-danger" role="alert"><?php echo $zip_error; ?></div>
                      <?php } ?>
                      <?php if ( $zip_message <> '' ) { ?>
                        <div class="alert alert-success" role="alert"><?php echo $zip_message; ?></div>
                      <?php } ?>
                        <p>Je kunt hier een zip downloaden met alle fotostroken van een GRATIS set.<br>Foto's worden na 1 maand automatisch definitief van de server verwijderd, dan werkt dit niet meer. Dus doe dit op tijd.</p>
                      <form method="post" action="page_event_zip.php">
                        <input type="hidden" name="mode" value="create_zip">
                        <div class="form-group">
                          <label for="set_id">Kies set</label>
                          <select class="form-control" id="set_id" name="set_id" required>
                            <option value="">Selecteer een set</option>
                            <?php
                              $sql_sets = "SELECT set_id, set_name FROM photobooth_set WHERE set_price_print = 0 AND event_id = '" . $event_id . "' ORDER BY set_id ASC";
                              $result_sets = mysqli_query( $cn , $sql_sets );
                              while ( $set = mysqli_fetch_object($result_sets) ) {
                                $selected = ( $set_id == $set->set_id ) ? ' selected' : '';
                                echo '<option value="' . $set->set_id . '"' . $selected . '>' . $set->set_id . ' - ' . $set->set_name . '</option>';
                              }
                            ?>
                          </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Genereer zip</button>
                      </form>

                      <?php if ( $zip_url <> '' && $zip_error == '' ) { ?>
                        <div class="mt-3">
                          <a class="btn btn-success" href="<?php echo $zip_url; ?>" target="_blank">Download zip</a>
                        </div>
                      <?php } ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </main>
      </div>
<?php include ("partial_footer.php"); ?>
