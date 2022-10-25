<?php
session_start();
if (!isset($_SESSION['connecte']) || $_SESSION['connecte'] == false) {
    header("location: index.php");
    exit;
}

include 'ouvrirconnexion.php';
try {
    // On se connecte à la BDD
    $conn = OuvrirConnexion();

    $id_utilisateur = $_SESSION['id_utilisateur'];

    $query = "SELECT * FROM `utilisateurs_adresses` WHERE `id_utilisateur` = '$id_utilisateur'";
    $result = mysqli_query($conn, $query);
    $count = mysqli_num_rows($result);

    // Si l'utilisateur n'a pas d'adresse
    if ($count == 0) {
        $hasAdresse = false;
    } else {
        $hasAdresse = true;
        $adresses = array();

        $query = "SELECT adresses.rue, adresses.numero, adresses.code_postal, adresses.ville, adresses.pays FROM utilisateurs_adresses JOIN adresses ON utilisateurs_adresses.id_adresse = adresses.id WHERE utilisateurs_adresses.id_utilisateur = '$id_utilisateur'";
        $result = mysqli_query($conn, $query);
        $count = mysqli_num_rows($result);
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            array_push($adresses, $row["numero"] . " " . lcfirst($row["rue"]) . ", " . $row["code_postal"] . ", " . $row["ville"] . ", " . $row["pays"]);
        }
    }

    if (isset($_POST['addadress'])) {
        $veutAjouterAdresse = true;
    }
} catch (\Throwable $th) {
    array_push($erreurs, $th->getMessage());
}

// Définition de l'adresse de l'utilisateur 
if (isset($_POST['setadress']) && isset($conn)) {
    do {
        // On récupère les valeurs du formulaire
        $rue = mysqli_real_escape_string($conn, htmlspecialchars($_POST['rue']));
        $numero = mysqli_real_escape_string($conn, htmlspecialchars($_POST['numero']));
        $ville = mysqli_real_escape_string($conn, htmlspecialchars($_POST['ville']));
        $code_postal = mysqli_real_escape_string($conn, htmlspecialchars($_POST['postal']));
        $pays = mysqli_real_escape_string($conn, htmlspecialchars($_POST['pays']));

        if (empty($rue) || empty($numero) || empty($ville) || empty($code_postal) || empty($pays)) {
            array_push($erreurs, "Un des champs requis est vide");
            break;
        }

        // Insertion d'une nouvelle adresse
        // Il faudrait vérifier si l'adresse existe pas déjà et récuperer simplement son id ?
        $query = "INSERT INTO `adresses` (`rue`, `numero`, `ville`, `code_postal`, `pays`) VALUES ('$rue', '$numero', '$ville', '$code_postal', '$pays')";
        if (mysqli_query($conn, $query)) {
            $id_adresse = mysqli_insert_id($conn);
        } else {
            array_push($erreurs, mysqli_error($conn));
            break;
        }

        // Si l'insertion de l'adresse fonctionné, on stop
        if (!isset($id_adresse)) {
            array_push($erreurs, "Erreur lors de la création de l'adresse, impossible de la lier à l'utilisateur");
            break;
        }

        // Si tout est bon, on lie l'adresse à l'utilisateur
        $query = "INSERT INTO `utilisateurs_adresses` (`id_utilisateur`, `id_adresse`) VALUES ('$id_utilisateur', '$id_adresse')";
        if (mysqli_query($conn, $query)) {
            FermerConnexion($conn);
            // On ajoute un message en variable de session pour qu'il puisse être affiché sur la page suivante
            $_SESSION['successMessage'] = "Adresse ajoutée à votre compte";
            header('location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            array_push($erreurs, mysqli_error($conn));
            break;
        }
    } while (0);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="dist/output.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <title>Compte - MealRush</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('navbar.php'); ?>

    <!-- Formulaire d'ajoutr d'adresse -->
    <?php if (!$hasAdresse || $veutAjouterAdresse) : ?>

        <div class="flex align-middle justify-center">
            <div class="rounded-xl shadow-xl bg-base-100 p-10 m-5 lg:m-10 lg:w-1/3">
                <img src="img/logo-blanc.png" alt="" class="w-64 mx-auto">
                <div class="divider"></div>
                <?php if ($veutAjouterAdresse) : ?>
                    <h1 class="text-xl font-bold md:text-2xl mb-5">
                        Ajouter une adresse
                    </h1>
                <?php else : ?>
                    <h1 class="text-xl font-bold md:text-2xl mb-5">
                        Finalisez la configuration de votre compte
                    </h1>
                <?php endif; ?>
                <form class="form-control w-full max-w-xs md:max-w-md gap-5" method="post">
                    <div class="grid grid-cols-2 gap-4">
                        <div id="row-1">
                            <label for="numero" class="label">
                                <span class="label-text">Numéro de rue</span>
                            </label>
                            <input type="text" name="numero" id="numero" placeholder="5" class="input input-bordered bg-slate-100 w-full" required />
                        </div>
                        <div id="row-2">
                            <label for="rue" class="label">
                                <span class="label-text">Rue</span>
                            </label>
                            <input type="text" name="rue" id="rue" placeholder="rue de Rivoli" class="input input-bordered bg-slate-100 w-full" required />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <div id="row-1">
                            <label for="ville" class="label">
                                <span class="label-text">Ville</span>
                            </label>
                            <input type="text" name="ville" id="ville" placeholder="Paris" class="input input-bordered bg-slate-100 w-full" required />
                        </div>
                        <div id="row-2">
                            <label for="postal" class="label">
                                <span class="label-text">Code postal</span>
                            </label>
                            <input type="text" name="postal" id="postal" placeholder="75001" class="input input-bordered bg-slate-100 w-full" required />
                        </div>
                    </div>
                    <!-- Sélection du pays -->
                    <select class="select select-bordered w-full" name="pays">
                        <option value="Afghanistan">Afghanistan</option>
                        <option value="Åland Islands">Åland Islands</option>
                        <option value="Albania">Albania</option>
                        <option value="Algeria">Algeria</option>
                        <option value="American Samoa">American Samoa</option>
                        <option value="Andorra">Andorra</option>
                        <option value="Angola">Angola</option>
                        <option value="Anguilla">Anguilla</option>
                        <option value="Antarctica">Antarctica</option>
                        <option value="Antigua and Barbuda">Antigua and Barbuda</option>
                        <option value="Argentina">Argentina</option>
                        <option value="Armenia">Armenia</option>
                        <option value="Aruba">Aruba</option>
                        <option value="Australia">Australia</option>
                        <option value="Austria">Austria</option>
                        <option value="Azerbaijan">Azerbaijan</option>
                        <option value="Bahamas">Bahamas</option>
                        <option value="Bahrain">Bahrain</option>
                        <option value="Bangladesh">Bangladesh</option>
                        <option value="Barbados">Barbados</option>
                        <option value="Belarus">Belarus</option>
                        <option value="Belgium">Belgium</option>
                        <option value="Belize">Belize</option>
                        <option value="Benin">Benin</option>
                        <option value="Bermuda">Bermuda</option>
                        <option value="Bhutan">Bhutan</option>
                        <option value="Bolivia">Bolivia</option>
                        <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
                        <option value="Botswana">Botswana</option>
                        <option value="Bouvet Island">Bouvet Island</option>
                        <option value="Brazil">Brazil</option>
                        <option value="British Indian Ocean Territory">British Indian Ocean Territory</option>
                        <option value="Brunei Darussalam">Brunei Darussalam</option>
                        <option value="Bulgaria">Bulgaria</option>
                        <option value="Burkina Faso">Burkina Faso</option>
                        <option value="Burundi">Burundi</option>
                        <option value="Cambodia">Cambodia</option>
                        <option value="Cameroon">Cameroon</option>
                        <option value="Canada">Canada</option>
                        <option value="Cape Verde">Cape Verde</option>
                        <option value="Cayman Islands">Cayman Islands</option>
                        <option value="Central African Republic">Central African Republic</option>
                        <option value="Chad">Chad</option>
                        <option value="Chile">Chile</option>
                        <option value="China">China</option>
                        <option value="Christmas Island">Christmas Island</option>
                        <option value="Cocos (Keeling) Islands">Cocos (Keeling) Islands</option>
                        <option value="Colombia">Colombia</option>
                        <option value="Comoros">Comoros</option>
                        <option value="Congo">Congo</option>
                        <option value="Congo, The Democratic Republic of The">Congo, The Democratic Republic of The</option>
                        <option value="Cook Islands">Cook Islands</option>
                        <option value="Costa Rica">Costa Rica</option>
                        <option value="Cote D'ivoire">Cote D'ivoire</option>
                        <option value="Croatia">Croatia</option>
                        <option value="Cuba">Cuba</option>
                        <option value="Cyprus">Cyprus</option>
                        <option value="Czech Republic">Czech Republic</option>
                        <option value="Denmark">Denmark</option>
                        <option value="Djibouti">Djibouti</option>
                        <option value="Dominica">Dominica</option>
                        <option value="Dominican Republic">Dominican Republic</option>
                        <option value="Ecuador">Ecuador</option>
                        <option value="Egypt">Egypt</option>
                        <option value="El Salvador">El Salvador</option>
                        <option value="Equatorial Guinea">Equatorial Guinea</option>
                        <option value="Eritrea">Eritrea</option>
                        <option value="Estonia">Estonia</option>
                        <option value="Ethiopia">Ethiopia</option>
                        <option value="Falkland Islands (Malvinas)">Falkland Islands (Malvinas)</option>
                        <option value="Faroe Islands">Faroe Islands</option>
                        <option value="Fiji">Fiji</option>
                        <option value="Finland">Finland</option>
                        <option value="France" selected>France</option>
                        <option value="French Guiana">French Guiana</option>
                        <option value="French Polynesia">French Polynesia</option>
                        <option value="French Southern Territories">French Southern Territories</option>
                        <option value="Gabon">Gabon</option>
                        <option value="Gambia">Gambia</option>
                        <option value="Georgia">Georgia</option>
                        <option value="Germany">Germany</option>
                        <option value="Ghana">Ghana</option>
                        <option value="Gibraltar">Gibraltar</option>
                        <option value="Greece">Greece</option>
                        <option value="Greenland">Greenland</option>
                        <option value="Grenada">Grenada</option>
                        <option value="Guadeloupe">Guadeloupe</option>
                        <option value="Guam">Guam</option>
                        <option value="Guatemala">Guatemala</option>
                        <option value="Guernsey">Guernsey</option>
                        <option value="Guinea">Guinea</option>
                        <option value="Guinea-bissau">Guinea-bissau</option>
                        <option value="Guyana">Guyana</option>
                        <option value="Haiti">Haiti</option>
                        <option value="Heard Island and Mcdonald Islands">Heard Island and Mcdonald Islands</option>
                        <option value="Holy See (Vatican City State)">Holy See (Vatican City State)</option>
                        <option value="Honduras">Honduras</option>
                        <option value="Hong Kong">Hong Kong</option>
                        <option value="Hungary">Hungary</option>
                        <option value="Iceland">Iceland</option>
                        <option value="India">India</option>
                        <option value="Indonesia">Indonesia</option>
                        <option value="Iran, Islamic Republic of">Iran, Islamic Republic of</option>
                        <option value="Iraq">Iraq</option>
                        <option value="Ireland">Ireland</option>
                        <option value="Isle of Man">Isle of Man</option>
                        <option value="Israel">Israel</option>
                        <option value="Italy">Italy</option>
                        <option value="Jamaica">Jamaica</option>
                        <option value="Japan">Japan</option>
                        <option value="Jersey">Jersey</option>
                        <option value="Jordan">Jordan</option>
                        <option value="Kazakhstan">Kazakhstan</option>
                        <option value="Kenya">Kenya</option>
                        <option value="Kiribati">Kiribati</option>
                        <option value="Korea, Democratic People's Republic of">Korea, Democratic People's Republic of</option>
                        <option value="Korea, Republic of">Korea, Republic of</option>
                        <option value="Kuwait">Kuwait</option>
                        <option value="Kyrgyzstan">Kyrgyzstan</option>
                        <option value="Lao People's Democratic Republic">Lao People's Democratic Republic</option>
                        <option value="Latvia">Latvia</option>
                        <option value="Lebanon">Lebanon</option>
                        <option value="Lesotho">Lesotho</option>
                        <option value="Liberia">Liberia</option>
                        <option value="Libyan Arab Jamahiriya">Libyan Arab Jamahiriya</option>
                        <option value="Liechtenstein">Liechtenstein</option>
                        <option value="Lithuania">Lithuania</option>
                        <option value="Luxembourg">Luxembourg</option>
                        <option value="Macao">Macao</option>
                        <option value="Macedonia, The Former Yugoslav Republic of">Macedonia, The Former Yugoslav Republic of</option>
                        <option value="Madagascar">Madagascar</option>
                        <option value="Malawi">Malawi</option>
                        <option value="Malaysia">Malaysia</option>
                        <option value="Maldives">Maldives</option>
                        <option value="Mali">Mali</option>
                        <option value="Malta">Malta</option>
                        <option value="Marshall Islands">Marshall Islands</option>
                        <option value="Martinique">Martinique</option>
                        <option value="Mauritania">Mauritania</option>
                        <option value="Mauritius">Mauritius</option>
                        <option value="Mayotte">Mayotte</option>
                        <option value="Mexico">Mexico</option>
                        <option value="Micronesia, Federated States of">Micronesia, Federated States of</option>
                        <option value="Moldova, Republic of">Moldova, Republic of</option>
                        <option value="Monaco">Monaco</option>
                        <option value="Mongolia">Mongolia</option>
                        <option value="Montenegro">Montenegro</option>
                        <option value="Montserrat">Montserrat</option>
                        <option value="Morocco">Morocco</option>
                        <option value="Mozambique">Mozambique</option>
                        <option value="Myanmar">Myanmar</option>
                        <option value="Namibia">Namibia</option>
                        <option value="Nauru">Nauru</option>
                        <option value="Nepal">Nepal</option>
                        <option value="Netherlands">Netherlands</option>
                        <option value="Netherlands Antilles">Netherlands Antilles</option>
                        <option value="New Caledonia">New Caledonia</option>
                        <option value="New Zealand">New Zealand</option>
                        <option value="Nicaragua">Nicaragua</option>
                        <option value="Niger">Niger</option>
                        <option value="Nigeria">Nigeria</option>
                        <option value="Niue">Niue</option>
                        <option value="Norfolk Island">Norfolk Island</option>
                        <option value="Northern Mariana Islands">Northern Mariana Islands</option>
                        <option value="Norway">Norway</option>
                        <option value="Oman">Oman</option>
                        <option value="Pakistan">Pakistan</option>
                        <option value="Palau">Palau</option>
                        <option value="Palestinian Territory, Occupied">Palestinian Territory, Occupied</option>
                        <option value="Panama">Panama</option>
                        <option value="Papua New Guinea">Papua New Guinea</option>
                        <option value="Paraguay">Paraguay</option>
                        <option value="Peru">Peru</option>
                        <option value="Philippines">Philippines</option>
                        <option value="Pitcairn">Pitcairn</option>
                        <option value="Poland">Poland</option>
                        <option value="Portugal">Portugal</option>
                        <option value="Puerto Rico">Puerto Rico</option>
                        <option value="Qatar">Qatar</option>
                        <option value="Reunion">Reunion</option>
                        <option value="Romania">Romania</option>
                        <option value="Russian Federation">Russian Federation</option>
                        <option value="Rwanda">Rwanda</option>
                        <option value="Saint Helena">Saint Helena</option>
                        <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
                        <option value="Saint Lucia">Saint Lucia</option>
                        <option value="Saint Pierre and Miquelon">Saint Pierre and Miquelon</option>
                        <option value="Saint Vincent and The Grenadines">Saint Vincent and The Grenadines</option>
                        <option value="Samoa">Samoa</option>
                        <option value="San Marino">San Marino</option>
                        <option value="Sao Tome and Principe">Sao Tome and Principe</option>
                        <option value="Saudi Arabia">Saudi Arabia</option>
                        <option value="Senegal">Senegal</option>
                        <option value="Serbia">Serbia</option>
                        <option value="Seychelles">Seychelles</option>
                        <option value="Sierra Leone">Sierra Leone</option>
                        <option value="Singapore">Singapore</option>
                        <option value="Slovakia">Slovakia</option>
                        <option value="Slovenia">Slovenia</option>
                        <option value="Solomon Islands">Solomon Islands</option>
                        <option value="Somalia">Somalia</option>
                        <option value="South Africa">South Africa</option>
                        <option value="South Georgia and The South Sandwich Islands">South Georgia and The South Sandwich Islands</option>
                        <option value="Spain">Spain</option>
                        <option value="Sri Lanka">Sri Lanka</option>
                        <option value="Sudan">Sudan</option>
                        <option value="Suriname">Suriname</option>
                        <option value="Svalbard and Jan Mayen">Svalbard and Jan Mayen</option>
                        <option value="Swaziland">Swaziland</option>
                        <option value="Sweden">Sweden</option>
                        <option value="Switzerland">Switzerland</option>
                        <option value="Syrian Arab Republic">Syrian Arab Republic</option>
                        <option value="Taiwan">Taiwan</option>
                        <option value="Tajikistan">Tajikistan</option>
                        <option value="Tanzania, United Republic of">Tanzania, United Republic of</option>
                        <option value="Thailand">Thailand</option>
                        <option value="Timor-leste">Timor-leste</option>
                        <option value="Togo">Togo</option>
                        <option value="Tokelau">Tokelau</option>
                        <option value="Tonga">Tonga</option>
                        <option value="Trinidad and Tobago">Trinidad and Tobago</option>
                        <option value="Tunisia">Tunisia</option>
                        <option value="Turkey">Turkey</option>
                        <option value="Turkmenistan">Turkmenistan</option>
                        <option value="Turks and Caicos Islands">Turks and Caicos Islands</option>
                        <option value="Tuvalu">Tuvalu</option>
                        <option value="Uganda">Uganda</option>
                        <option value="Ukraine">Ukraine</option>
                        <option value="United Arab Emirates">United Arab Emirates</option>
                        <option value="United Kingdom">United Kingdom</option>
                        <option value="United States">United States</option>
                        <option value="United States Minor Outlying Islands">United States Minor Outlying Islands</option>
                        <option value="Uruguay">Uruguay</option>
                        <option value="Uzbekistan">Uzbekistan</option>
                        <option value="Vanuatu">Vanuatu</option>
                        <option value="Venezuela">Venezuela</option>
                        <option value="Viet Nam">Viet Nam</option>
                        <option value="Virgin Islands, British">Virgin Islands, British</option>
                        <option value="Virgin Islands, U.S.">Virgin Islands, U.S.</option>
                        <option value="Wallis and Futuna">Wallis and Futuna</option>
                        <option value="Western Sahara">Western Sahara</option>
                        <option value="Yemen">Yemen</option>
                        <option value="Zambia">Zambia</option>
                        <option value="Zimbabwe">Zimbabwe</option>
                    </select>
                    <div class="gap-0">
                        <?php if ($veutAjouterAdresse) : ?>
                            <a class="btn btn-block btn-ghost border-black mt-5" href="compte.php">Annuler</a>
                        <?php endif; ?>
                        <button class="btn btn-block btn-neutral mt-5" name="setadress">Valider</button>
                    </div>
                </form>
            </div>
        </div>

    <?php else : ?>


        <div class="mx-auto p-10">
            <h1 class="text-2xl font-bold md:text-3xl text-center">Ravis de vous revoir, <?php echo $_SESSION['prenom']; ?>&nbsp;!</h1>

            <?php if (empty($_GET['modification'])) : ?>
                <div class="card w-fit min-w-[40%] shadow-md my-10 mx-auto">

                    <div class="avatar placeholder mx-auto mt-5">
                        <div class="bg-neutral-focus text-neutral-content rounded-full w-24">
                            <!-- On récupère la première lettre du prenom pour l'avatar -->
                            <span class="text-3xl uppercase"><?php echo mb_substr($_SESSION['prenom'], 0, 1); ?></span>
                        </div>
                    </div>
                    <div class="card-body items-center text-center">
                        <h2 class="card-title"><?php echo $_SESSION['prenom'] . " " . $_SESSION['nom']; ?></h2>
                        <p><?php echo $_SESSION['email'] ?></p>
                        <div class="card-actions justify-center">
                            <a class="btn btn-ghost" href="#ouvrir-adresses">Adresses</a>
                            <a class="btn btn-ghost" href="#ouvrir-moyens-de-paiement">Moyens de paiement</a>
                        </div>
                        <a class="btn btn-neutral" href="?modification=1">Modifier les informations</a>
                    </div>

                    <div class="stats">

                        <div class="stat place-items-center">
                            <div class="stat-title">Activité</div>
                            <div class="stat-value">0</div>
                            <div class="stat-desc">commandes</div>
                        </div>

                        <div class="stat place-items-center">
                            <div class="stat-title">Ancienneté</div>
                            <div class="stat-value">
                                <?php
                                $maintenant = new DateTime();
                                $creation = new DateTime($_SESSION['creation']);

                                $jours_anciennete = $creation->diff($maintenant)->format("%a");

                                echo $jours_anciennete;
                                ?>
                            </div>
                            <div class="stat-desc">jours</div>
                        </div>

                    </div>
                </div>
            <?php else : ?>
                <!-- Mode modification -->
                <div class="card w-fit shadow-md my-10 mx-auto">
                    <div class="avatar placeholder mx-auto mt-5">
                        <div class="bg-neutral-focus text-neutral-content rounded-full w-24">
                            <!-- On récupère la première lettre du prenom pour l'avatar -->
                            <span class="text-3xl uppercase"><?php echo mb_substr($_SESSION['prenom'], 0, 1); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form class="form-control items-center gap-2" method="post" action="compte.php">
                            <div class="grid grid-cols-2 gap-4">
                                <div id="row-1">
                                    <input type="text" value="<?php echo $_SESSION['prenom'] ?>" class="card-title text-center input input-bordered bg-slate-100 w-full" />
                                </div>
                                <div id="row-2">
                                    <input type="text" value="<?php echo $_SESSION['nom'] ?>" class="card-title text-center input input-bordered bg-slate-100 w-full" />
                                </div>
                            </div>
                            <input type="text" value="<?php echo $_SESSION['email'] ?>" class="text-center input input-bordered bg-slate-100 w-full" />
                            <div class="card-actions">
                                <button class="btn btn-neutral mt-5 gap-2"">
                                    Enregistrer
                                    <svg xmlns=" http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 stroke-current">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>

    <?php include('footer.php'); ?>

    <div class="modal" id="ouvrir-adresses">
        <div class="modal-box text-center">
            <h3 class="font-bold text-lg mb-5">Adresses enregistrées</h3>
            <?php foreach ($adresses as $a) : ?>
                <button class="btn btn-ghost gap-2">
                    <?php echo $a; ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M 18 2 L 15.585938 4.4140625 L 19.585938 8.4140625 L 22 6 L 18 2 z M 14.076172 5.9238281 L 3 17 L 3 21 L 7 21 L 18.076172 9.9238281 L 14.076172 5.9238281 z" />
                    </svg>
                </button>
            <?php endforeach; ?>
            <div class="modal-action">
                <form method="post" action="compte.php">
                    <button class="btn btn-ghost" name="addadress">Ajouter une adresse</button>
                </form>
                <!-- Si on vient ici depuis la page d'accueil, on renvoie vers cette dernière au lieu de rester sur la page de compte -->
                <?php if (empty($_GET['selection'])) : ?>
                    <a href="#" class="btn">Terminé</a>
                <?php else : ?>
                    <a href="index.php#adresse" class="btn">Terminé</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal" id="ouvrir-moyens-de-paiement">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Moyens de paiement enregistrés</h3>
            <p class="py-4"></p>
            <div class="modal-action">
                <a href="#" class="btn">Terminé</a>
            </div>
        </div>
    </div>

    <script>
        // Empêche de resoumettre le formulaire quand on refresh la page
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>

</body>

</html>