<?php

//************************************************************************
if (eregi("class.wcts.php",$_SERVER['PHP_SELF'])) die();
//************************************************************************

class wcts {
		
	/*************************************
	Funció global: Geodèsiques a UTM amb canvi de datum
		$lambda: Longitud, en graus decimals
		$phi: Latitud, en graus decimals
		$h: Alçada elipsoidal (opcional, veure docs)
		$elipsoide_in: Elipsoide origen (datum origen)
		$elipsoide_out: Elipsoide destí (datum destí)
	
	*************************************/
	function ged2utm_datum($lambda,$phi,$elipsoide_in = "WGS84",$elipsoide_out = "International 1924",$parameters = 1,$h=false) {

		$gec_datum1 = $this->ged2gec($lambda,$phi,$elipsoide_in);
		$gec_datum2 = $this->trans_gec($gec_datum1[0],$gec_datum1[1],$gec_datum1[2],$parameters);
		$ged_datum2 = $this->gec2ged($gec_datum2[0],$gec_datum2[1],$gec_datum2[2],$elipsoide_out);
		$utm_datum2 = $this->ged2utm($ged_datum2[0],$ged_datum2[1],$elipsoide_out);
		
		$results = array($utm_datum2[0],$utm_datum2[1],$utm_datum2[2]);
		return $results;
	}

	/*************************************
	Funció global: UTM a Geodèsiques amb canvi de datum
		$x: X UTM
		$y: Y UTM
		$elipsoide_in: Elipsoide origen (datum origen)
		$elipsoide_out: Elipsoide origen (datum origen)
		$parameters: Joc de paràmetres transformació
		$fus: Fus
	*************************************/
	function utm2ged_datum($x,$y,$elipsoide_in = "International 1924" ,$elipsoide_out = "WGS84",$parameters = 2,$fus= 31) {
		
		$ged_datum1 = $this->utm2ged($x,$y,$fus,$elipsoide_in);
		$gec_datum1 = $this->ged2gec($ged_datum1[0],$ged_datum1[1],$elipsoide_in);
		$gec_datum2 = $this->trans_gec($gec_datum1[0],$gec_datum1[1],$gec_datum1[2],$parameters);
		$ged_datum2 = $this->gec2ged($gec_datum2[0],$gec_datum2[1],$gec_datum2[2],$elipsoide_out);
		
		$results = array($ged_datum2[0],$ged_datum2[1]);
		return $results;
		
	}





	/*************************************
	Pas 1: Geodèsiques a Geocèntriques (dins del mateix datum)
		$lambda: Longitud (geodèsiques sexadecimals)
		$phi: Latitud (geodèsiques sexadecimals)
		$h: Alçada elipsoidal (opcional, veure docs)
		$elipsoide: Elipsoide (datum)
	
	*************************************/
	function ged2gec($lambda,$phi,$elipsoide = "WGS84",$h=false){
		
		//Obtenim els paràmetres de l'elipsoide (datum)
		$datum = $this->datums[$elipsoide];
		
		//Passem a radians les coordenades
		$lambda = $lambda * (pi() /180);
		$phi = $phi * (pi() /180);
		
		//Calculem un paràmetre
		$n =(pow($datum["a"],2))/sqrt(  pow($datum["a"],2)* pow(cos($phi),2)  +  pow($datum["b"],2)*pow(sin($phi),2)  ); 
		
		//Calculem les coordenades (geocèntriques)
		$this->x_ecef = +($n+$h)*cos($phi)*cos($lambda);
		$this->y_ecef = +($n+$h)*cos($phi)*sin($lambda);
		$this->z_ecef =((pow($datum["b"],2)/pow($datum["a"],2))*$n+$h)*sin($phi);
	
		$results = array($this->x_ecef,$this->y_ecef,$this->z_ecef);
		return $results;
	}
	
	/*************************************
		Pas 2: Transformació entre Datums (entre coordenades geocèntriques)
		$x_ecef: x (geocèntriques)
		$y_ecef: y (geocèntriques)
		$z_ecef: z (geocèntriques) 
		$parameters: Codi del joc de paràmetres (vegeu var $trans_params)
	
	*************************************/
	function trans_gec($x_ecef,$y_ecef,$z_ecef,$parameters) {
		//Obtenim els paràmetres de transformació
		$params = $this->trans_params[$parameters];
		
		//Transformem el factor d'escala
		$S = $params["S"]/ 1000000;
		
		//Calculem les coordenades transformades al datum de destí
		$this->x_trans = +$params["AX"]+((1+$S)*($x_ecef+($y_ecef*deg2rad($params["RZ"]/60/60))-($z_ecef*deg2rad($params["RY"]/60/60))));
		$this->y_trans = +$params["AY"]+((1+$S)*(-(deg2rad($params["RZ"]/60/60)*$x_ecef)+$y_ecef+($z_ecef*deg2rad($params["RX"]/60/60))));
		$this->z_trans = +$params["AZ"]+((1+$S)*(($x_ecef*deg2rad($params["RY"]/60/60))-($y_ecef*deg2rad($params["RX"]/60/60))+$z_ecef));

		$results = array($this->x_trans,$this->y_trans,$this->z_trans);
		return $results;
	}
	
	/*************************************
		Pas 3: Geocèntriques a Geodèsiques (dins del mateix datum)
		$x_ecef: x (geocèntriques)
		$y_ecef: y (geocèntriques)
		$z_ecef: z (geocèntriques) 
		$elipsoide: Elipsoide (datum)
		
	*************************************/
	function gec2ged($x_ecef,$y_ecef,$z_ecef,$elipsoide = "International 1924") {
		
		//Obtenim els paràmetres de l'elipsoide (datum)
		$datum = $this->datums[$elipsoide];
		
		//Calculem els paràmetres necessaris
		$e2 = (pow($datum["a"],2)-pow($datum["b"],2))/(pow($datum["a"],2));
		$e_2 = (pow($datum["a"],2)-pow($datum["b"],2))/(pow($datum["b"],2));
		$p = +sqrt(pow($x_ecef,2)+pow($y_ecef,2));
		$theta = atan(($z_ecef*$datum["a"])/($p*$datum["b"]));
		$phi_rad = +atan((+$z_ecef+$e_2*$datum["b"]*pow(sin($theta),3))/($p-$e2*$datum["a"]*pow(cos($theta),3)));
		$lambda_rad =+atan($y_ecef/$x_ecef);
		$n = +pow($datum["a"],2)/(sqrt(pow($datum["a"],2)*pow(cos($phi_rad),2)+ pow($datum["b"],2)*pow(sin($phi_rad),2)));
		
		$h = +($p/cos($phi_rad))-$n;
		
		//Calculem les coordenades (geodèsiques decimals)
		$this->lambda =rad2deg($lambda_rad);
		$this->phi =rad2deg($phi_rad);
		$this->h = $h;

		$results = array($this->lambda,$this->phi,$this->h);
		return $results;
	
	}

	/*************************************
	Pas 4: Geodèsiques a UTM (dins del mateix datum)
		$lambda: Longitud, en graus decimals
		$phi: Latitud, en graus decimals
		$elipsoide: Elipsoide (datum)
	
	*************************************/
	function ged2utm($lambda,$phi,$elipsoide = "WGS84") {
	
		//Obtenim els paràmetres de l'elipsoide (datum)
		$datum = $this->datums[$elipsoide]; 
		
		//Calculem el fus
		$fus = floor(($lambda/6)+31);
		
		//Calculem el meridià central del fus
		$meridia = (6 * $fus) - 183;
		
		//Passem a radians les coordenades
		$lambda = $lambda * (pi() /180);
		$phi = $phi * (pi() /180);
		
		// Calculem la distancia angular entre la longitud del punt i el meridià central del fus (passant-lo a radians)
		$dif_lambda = $lambda - ($meridia * (pi() /180));
		
		//Calculem paràmetres necessaris per a la conversió
		$A = cos($phi)*sin($dif_lambda);
		$xi = (1/2)*log(((1+$A)/(1-$A)),exp(1));
		$eta = atan((tan($phi))/(cos($dif_lambda)))-$phi;
		$ni = ($datum["c"] /pow(1+$datum["e_2"]*pow(cos($phi),2),(1/2))  )*0.9996;
		$Zeta = ($datum["e_2"]/2)*pow($xi,2)*pow(cos($phi),2);
		$a1 = sin(2*$phi);
		$a2 = $a1 * (pow(cos($phi),2)); //signe + (abs!!)
		$j2 = $phi + ($a1/2);
		$j4 = ((3*$j2)+$a2)/4;
		$j6 = (5*$j4+$a2*(pow(cos($phi),2)))/3;
		$alfa = (3/4)*$datum["e_2"];
		$beta = (5/3)*pow($alfa,2);
		$gamma = (35/27)*pow($alfa,3);
		$B_fi = 0.9996*$datum["c"]*($phi-($alfa*$j2)+($beta*$j4)-($gamma*$j6));
		
		//Calculem les noves coordenades
		$this->x = $xi*$ni*(1+$Zeta/3)+500000;
		$this->y = $eta*$ni*(1+$Zeta)+$B_fi;
		if ($phi < 0) $this->y = $this->y + 10000000;
		$this->fus = $fus;
		
		$results = array($this->x,$this->y,$this->fus);
		return $results;
	}
	
	/*************************************
	UTM a Geodèsiques (dins del mateix datum)
		$x: X UTM
		$y: Y UTM
		$fus: Fus
		$elipsoide: Elipsoide (datum)
	
	*************************************/
	function utm2ged($x,$y,$fus = 31,$elipsoide = "WGS84") {
		//Obtenim els paràmetres de l'elipsoide (datum)
		$datum = $this->datums[$elipsoide]; 
		
		//Calculem el meridià central del fus
		$meridia = (6 * $fus) - 183;
		
		//Calculem paràmetres necessaris
		$phi_ = $y / (6366197.724*0.9996);
		$ni = ($datum["c"] /pow(1+$datum["e_2"]*pow(cos($phi_),2),(1/2))  )*0.9996;
		$a =($x-500000)/$ni;
		$a1 = sin(2*$phi_);
		$a2 = $a1 * (pow(cos($phi_),2)); 	
		$j2 = $phi_ + ($a1/2);
		$j4 = ((3*$j2)+$a2)/4;
		$j6 = (5*$j4+$a2*(pow(cos($phi_),2)))/3;
		$alfa = (3/4)*$datum["e_2"];
		$beta = (5/3)*pow($alfa,2);
		$gamma = (35/27)*pow($alfa,3);
		$B_fi = 0.9996*$datum["c"]*($phi_-($alfa*$j2)+($beta*$j4)-($gamma*$j6));
		$b = ($y-$B_fi)/$ni;
		$Zeta = (($datum["e_2"]*pow($a,2))/2)*pow((cos($phi_)),2);
		$xi = $a*(1-($Zeta/3));
		$eta = $b*(1-$Zeta)+$phi_;
		$sin_h_xi = (exp($xi)-exp($xi*-1))/2;
		$dif_lambda = atan($sin_h_xi/cos($eta));
		$tau = atan(cos($dif_lambda)*tan($eta));
		
		//$phi_rad = $phi_+(1+$datum["e_2"]*pow(cos($phi_),2)-(3/2)*$datum["e_2"]*sin($phi)*cos($phi)*($tau-$phi))*($tau-$phi_);
		$phi_rad = $phi_ + (1 + $datum["e_2"]*(pow(cos($phi_),2)) - (3/2)*$datum["e_2"]*sin($phi_)*cos($phi_)*($tau-$phi_))*($tau-$phi_);
	
		//Calculem les coordenades (geodèsiques sexadecimals)
		$this->lambda = ($dif_lambda/pi())*180+$meridia;
		$this->phi = ($phi_rad/pi())*180;
		
		$results = array($this->lambda,$this->phi);
		return $results;

	}

/*****************************************************************************************
Constants
******************************************************************************************/

	/* Elipsoides (datums)
	 a: semieix major, b: semieix menor, e: excentricitat, e_: 2ona excentricitat, e_2: 2ona excentricitat al quadrat, c: radi polar de corbatura
	 *Nota: ED50 =+- International 1909/1924
	*/
	var $datums = array(
			"Airy 1830" => array("a" => 6377563.396,"b" => 6356256.91, "e" => 0.081673372, "e_" => 0.081947146, "e_2" => 0.006715335, "c" => 6398941.302), 
			"Airy Modified 1965" => array("a" => 6377340.189,"b" => 6356034.4479, "e" => 0.081673374, "e_" => 0.081947147, "e_2" => 0.006715335, "c" => 6398717.348),
			"Bessel 1841" => array("a" => 6377397.155000,"b" => 6356078.962840, "e" => 0.081696831, "e_" => 0.081970841, "e_2" => 0.006719219, "c" => 6398786.848),
			"Clarke 1866" => array("a" => 6378206.400000,"b" => 6356583.800000, "e" => 0.082271854, "e_" => 0.082551711, "e_2" => 0.006814785, "c" => 6399902.552),
			"Clarke 1880" => array("a" => 6378249.145000,"b" => 6356514.869550, "e" => 0.0824834, "e_" => 0.082765428, "e_2" => 0.006850116, "c" => 6400057.735),
			"Fischer 1960" => array("a" => 6378166.000000,"b" => 6356784.280000, "e" => 0.081813341, "e_" => 0.082088529, "e_2" => 0.006738527, "c" => 6399619.64),
			"Fischer 1968" => array("a" => 6378150.000000,"b" => 6356768.330000, "e" => 0.081813348, "e_" => 0.082088536, "e_2" => 0.006738528, "c" => 6399603.59),
			"GRS80" => array("a" => 6378137.000000,"b" => 6356752.314140, "e" => 0.081819191, "e_" => 0.082094438, "e_2" => 0.006739497, "c" => 6399593.626),
			"Hayford 1909" => array("a" => 6378388.000000,"b" => 6356911.946130, "e" => 0.08199189, "e_" => 0.08226889, "e_2" => 0.00676817, "c" => 6399936.608),
			"Helmert 1906" => array("a" => 6378200.000000,"b" => 6356818.170000, "e" => 0.081813333, "e_" => 0.082088521, "e_2" => 0.006738525, "c" => 6399653.75),
			"Hough 1960" => array("a" => 6378270.000000,"b" => 6356794.343479, "e" => 0.08199189, "e_" => 0.08226889, "e_2" => 0.00676817, "c" => 6399818.209),
			"International 1909" => array("a" =>	6378388.000000,"b" => 6356911.946130, "e" => 0.08199189, "e_" => 0.08226889, "e_2" => 0.00676817, "c" => 6399936.608),
			"International 1924" => array("a" =>	6378388.000000,"b" => 6356911.946130, "e" => 0.08199189, "e_" => 0.08226889, "e_2" => 0.00676817, "c" => 6399936.608),
			"Krasovsky 1940" => array("a" => 6378245.000000,"b" => 6356863.018800, "e" => 0.081813334, "e_" => 0.082088522, "e_2" => 0.006738525, "c" => 6399698.902),
			"Mercury 1960" => array("a" => 6378166.000000,"b" => 6356784.283666, "e" => 0.081813334, "e_" => 0.082088522, "e_2" => 0.006738525, "c" => 6399619.636),
			"Mercury Modificado 1968" => array("a" => 6378150.000000,"b" => 6356768.337303, "e" => 0.081813334, "e_" => 0.082088522, "e_2" => 0.006738525, "c" => 6399603.582),
			"Nuevo International 1967" => array("a" => 6378157.500000,"b" => 6356772.200000, "e" => 0.081820233, "e_" => 0.08209549, "e_2" => 0.00673967, "c" => 6399614.744),
			"Sudamericano 1969" => array("a" => 6378160.000000,"b" => 6356774.720000, "e" => 0.081820178, "e_" => 0.082095436, "e_2" => 0.006739661, "c" => 6399617.224),
			"Walbeck 1817" => array("a" => 6376896.000000,"b" => 6355834.846700, "e" => 0.081206823, "e_" => 0.081475916, "e_2" => 0.006638325, "c" => 6398026.943),
			"WGS66" => array("a" => 6378145.000000,"b" => 6356759.769356, "e" => 0.08182018, "e_" => 0.082095437, "e_2" => 0.006739661, "c" => 6399602.174),
			"WGS72" => array("a" => 6378135.000000,"b" => 6356750.519915, "e" => 0.081818811, "e_" => 0.082094054, "e_2" => 0.006739434, "c" => 6399591.419),
			"WGS84" => array("a" => 6378137.000000,"b" => 6356752.314, "e" => 0.081819191, "e_" => 0.082094438, "e_2" => 0.006739497, "c" => 6399593.626)
		);
		
		/* Paràmetres de transformació entre datums
			-Codis de joc de paràmetres
					1: Espanya (Peninsula Ibèrica Principal) de ETRS89 (~WGS84) a ED50	
					2: Espanya (Peninsula Ibèrica Principal) de ED50 a ETRS89 (~WGS84)	
					3: Espanya (Nordest Península Ibèrica) de ETRS89 (~WGS84) a ED50	
					4: Espanya (Nordest Península Ibèrica) de ED50 a ETRS89 (~WGS84)	
					5: Espanya (Illes Balears) de ETRS89 (~WGS84) a ED50	
					6: Espanya (Illes Balears) de ED50 a ETRS89 (~WGS84)	
			- AX,AY,AZ: increments de distància RX,RY,RZ: rotacions S: factor d'escala
	*/
	var $trans_params = array(
			1 => array("AX" => 131.0320,"AY" => 100.2510,"AZ" => 163.3540,"RX" => -1.2438,"RY" => -0.0195,"RZ" => -1.1436,"S" => -9.3900),
			2 => array("AX" => -131.0320,"AY" => -100.2510,"AZ" => -163.3540,"RX" => 1.2438,"RY" => 0.0195,"RZ" => 1.1436,"S" => 9.3900),
			3 => array("AX" => 178.3830,"AY" => 83.1720,"AZ" => 221.2930,"RX" => 0.5401,"RY" => -0.5319,"RZ" => -0.1263,"S" => -21.2000),
			4 => array("AX" => -178.3830,"AY" => -83.1720,"AZ" => -221.2930,"RX" => -0.5401,"RY" => 0.5319,"RZ" => 0.1263,"S" => 21.2000),
			5 => array("AX" => 181.4609,"AY" => 90.2931,"AZ" => 187.1902,"RX" => 0.1435,"RY" => 0.4922,"RZ" => -0.3935,"S" => -17.5700),
			6 => array("AX" => -181.4609,"AY" => -90.2931,"AZ" => -187.1902,"RX" => -0.1435,"RY" => -0.4922,"RZ" => 0.3935,"S" => 17.5700)
		);


}

?>
