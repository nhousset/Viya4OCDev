package main

import (
	"bufio"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"regexp"
	"sort"
	"strconv"
	"strings"
	"syscall"

	"golang.org/x/term"
)

// Définition des couleurs
const (
	ColorRed    = "\033[0;31m"
	ColorGreen  = "\033[0;32m"
	ColorYellow = "\033[1;33m"
	ColorBlue   = "\033[0;34m"
	ColorPurple = "\033[0;35m"
	ColorCyan   = "\033[0;36m"
	ColorBold   = "\033[1m"
	ColorReset  = "\033[0m"
)

var (
	scriptDir string
	cmdDir    string

	profileName = "default"
	configFile  string
	directCmd   string
	dryRun      bool

	configMap = make(map[string]string)
)

func main() {
	var err error
	scriptDir, err = os.Getwd()
	if err != nil {
		fmt.Println("Erreur lors de la récupération du répertoire courant:", err)
		os.Exit(1)
	}
	cmdDir = filepath.Join(scriptDir, "cmd")

	parseArgs()

	if configFile == "" {
		configFile = filepath.Join(scriptDir, fmt.Sprintf("config-%s.env", strings.ToLower(profileName)))
		if profileName == "default" {
			configFile = filepath.Join(scriptDir, "config.env")
		}
	}

	loadConfig()
	showDisclaimer()

	if directCmd != "" {
		targetScript := filepath.Join(cmdDir, directCmd)
		if _, err := os.Stat(targetScript); os.IsNotExist(err) {
			fmt.Printf("%s❌ Erreur : Le script '%s' est introuvable dans le dossier '%s'.%s\n", ColorRed, directCmd, cmdDir, ColorReset)
			os.Exit(1)
		}

		doLogin()

		fmt.Printf("\n%s🚀 Lancement direct : %s%s\n", ColorYellow, directCmd, ColorReset)
		fmt.Printf("%s--------------------------------------------------------------------------------------------%s\n", ColorBlue, ColorReset)

		printProdBanner()
		runScript(targetScript)
	} else {
		for {
			showMenu()
		}
	}
}

func parseArgs() {
	args := os.Args[1:]
	for i := 0; i < len(args); i++ {
		switch args[i] {
		case "--dry":
			dryRun = true
		case "-p", "--profile":
			if i+1 < len(args) {
				profileName = args[i+1]
				i++
			} else {
				fmt.Printf("%s❌ Erreur : l'argument --profile nécessite un nom de profil.%s\n", ColorRed, ColorReset)
				os.Exit(1)
			}
		case "-h", "--help":
			showHelp()
			os.Exit(0)
		case "--cmd":
			if i+1 < len(args) {
				directCmd = args[i+1]
				i++
			} else {
				fmt.Printf("%s❌ Erreur : l'argument --cmd nécessite le nom d'un script.%s\n", ColorRed, ColorReset)
				os.Exit(1)
			}
		default:
			fmt.Printf("%s❌ Option inconnue : %s%s\n", ColorRed, args[i], ColorReset)
			fmt.Println("Utilisez --help pour plus d'informations.")
			os.Exit(1)
		}
	}
}

func loadConfig() {
	if _, err := os.Stat(configFile); os.IsNotExist(err) {
		return
	}
	file, err := os.Open(configFile)
	if err != nil {
		return
	}
	defer file.Close()

	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())
		if strings.HasPrefix(line, "export ") {
			line = strings.TrimPrefix(line, "export ")
			parts := strings.SplitN(line, "=", 2)
			if len(parts) == 2 {
				key := parts[0]
				val := strings.Trim(parts[1], `"'`)
				configMap[key] = val
				os.Setenv(key, val)
			}
		}
	}
}

func saveToConfig(key, value string) {
	configMap[key] = value
	os.Setenv(key, value)

	var lines []string
	if b, err := os.ReadFile(configFile); err == nil {
		for _, line := range strings.Split(string(b), "\n") {
			line = strings.TrimRight(line, "\r")
			if line == "" {
				continue
			}
			if !strings.HasPrefix(line, "export "+key+"=") {
				lines = append(lines, line)
			}
		}
	}
	lines = append(lines, fmt.Sprintf(`export %s="%s"`, key, value))

	content := strings.Join(lines, "\n") + "\n"
	os.WriteFile(configFile, []byte(content), 0600)
}

func readInput(promptText string, defaultVal string) string {
	fmt.Print(promptText)
	reader := bufio.NewReader(os.Stdin)
	input, _ := reader.ReadString('\n')
	input = strings.TrimSpace(input)
	if input == "" {
		return defaultVal
	}
	return input
}

func readPassword(promptText string) string {
	fmt.Print(promptText)
	bytePassword, err := term.ReadPassword(int(syscall.Stdin))
	fmt.Println()
	if err != nil {
		return readInput("", "")
	}
	return strings.TrimSpace(string(bytePassword))
}

func checkAndPromptVars() {
	if configMap["ENV_TYPE"] == "" {
		fmt.Printf("%sInitialisation du profil : %s%s\n", ColorYellow, profileName, ColorReset)
		val := readInput("👉 Type d'environnement (ex: prod, dev, test) : ", "dev")
		saveToConfig("ENV_TYPE", val)
	}

	if configMap["TOKEN_URL"] == "" {
		if configMap["ENV_TYPE"] != "" {
			fmt.Printf("%sConfiguration suite...%s\n", ColorYellow, ColorReset)
		}
		fmt.Println("Pour vous connecter au cluster, vous aurez besoin d'aller chercher un token sur l'interface web OpenShift.")
		val := readInput("👉 URL pour récupérer le token OpenShift (ou 's' pour ignorer/skip) : ", "")
		if strings.ToLower(val) == "s" {
			val = "skip"
		}
		saveToConfig("TOKEN_URL", val)
	}

	if configMap["SERVER_URL"] == "" {
		val := readInput("👉 URL du cluster OpenShift : ", "")
		saveToConfig("SERVER_URL", val)
	}

	if configMap["TOKEN"] == "" {
		if configMap["TOKEN_URL"] != "" && configMap["TOKEN_URL"] != "skip" {
			fmt.Printf("\n%s ╭───────────────────────────────────────────────────────────%s\n", ColorPurple, ColorReset)
			fmt.Printf("%s │ %s👋 Bonjour ! Il nous faut un jeton (token) OpenShift.%s\n", ColorPurple, ColorYellow, ColorReset)
			fmt.Printf("%s │ %sVous pouvez en générer un tout neuf en un clic via ce lien :%s\n", ColorPurple, ColorReset, ColorReset)
			fmt.Printf("%s │ 🌐 %s%s%s%s\n", ColorPurple, ColorBold, ColorCyan, configMap["TOKEN_URL"], ColorReset)
			fmt.Printf("%s ╰───────────────────────────────────────────────────────────%s\n\n", ColorPurple, ColorReset)
		}
		val := readPassword("👉 Token de connexion OpenShift : ")
		saveToConfig("TOKEN", val)
	}

	if configMap["DEFAULT_NAMESPACE"] == "" {
		val := readInput("👉 Namespace SAS Viya [sas-viya] : ", "sas-viya")
		saveToConfig("DEFAULT_NAMESPACE", val)
	}

	if configMap["OC_BIN_PATH"] == "" {
		val := readInput("👉 Chemin COMPLET du binaire oc : ", "")
		saveToConfig("OC_BIN_PATH", val)
	}

	if ocPath := configMap["OC_BIN_PATH"]; ocPath != "" {
		if _, err := os.Stat(ocPath); err == nil {
			dir := filepath.Dir(ocPath)
			path := os.Getenv("PATH")
			os.Setenv("PATH", dir+string(os.PathListSeparator)+path)
		}
	}

	if configMap["INSECURE_SKIP_TLS_VERIFY"] == "" {
		saveToConfig("INSECURE_SKIP_TLS_VERIFY", "true")
	}
	if configMap["AUDIT_OUT_DIR"] == "" {
		saveToConfig("AUDIT_OUT_DIR", filepath.Join(scriptDir, "rapports_audit"))
	}
}

func runCmdSilently(name string, args ...string) error {
	cmd := exec.Command(name, args...)
	return cmd.Run()
}

func doLogin() {
	if dryRun {
		fmt.Printf("%s⚠️ Mode DRY RUN activé : Contournement de la connexion OpenShift.%s\n", ColorYellow, ColorReset)
		if configMap["DEFAULT_NAMESPACE"] == "" {
			os.Setenv("DEFAULT_NAMESPACE", "sas-viya-dryrun")
			configMap["DEFAULT_NAMESPACE"] = "sas-viya-dryrun"
		}
		return
	}

	checkAndPromptVars()

	var tlsOpt string
	if configMap["INSECURE_SKIP_TLS_VERIFY"] == "true" {
		tlsOpt = "--insecure-skip-tls-verify=true"
	}

	if err := runCmdSilently("oc", "whoami"); err == nil {
		runCmdSilently("oc", "project", configMap["DEFAULT_NAMESPACE"])
		return
	}

	fmt.Printf("%s🔌 Connexion à %s...%s\n", ColorCyan, configMap["SERVER_URL"], ColorReset)

	loginArgs := []string{"login", configMap["SERVER_URL"], "--token=" + configMap["TOKEN"]}
	if tlsOpt != "" {
		loginArgs = append(loginArgs, tlsOpt)
	}

	if err := runCmdSilently("oc", loginArgs...); err == nil {
		fmt.Printf("%s✅ Connexion réussie.%s\n", ColorGreen, ColorReset)
		runCmdSilently("oc", "project", configMap["DEFAULT_NAMESPACE"])
	} else {
		fmt.Printf("%s❌ Token invalide ou expiré.%s\n", ColorRed, ColorReset)

		fmt.Printf("\n%s ╭───────────────────────────────────────────────────────────%s\n", ColorPurple, ColorReset)
		fmt.Printf("%s │ %s💡 Oups ! Votre token est invalide ou a expiré.%s\n", ColorPurple, ColorYellow, ColorReset)
		if configMap["TOKEN_URL"] != "" && configMap["TOKEN_URL"] != "skip" {
			fmt.Printf("%s │ %sPas de panique, allez récupérer un nouveau token juste ici :%s\n", ColorPurple, ColorReset, ColorReset)
			fmt.Printf("%s │ 🌐 %s%s%s%s\n", ColorPurple, ColorBold, ColorCyan, configMap["TOKEN_URL"], ColorReset)
		} else {
			fmt.Printf("%s │ %sConnectez-vous à l'interface web OpenShift pour en générer un nouveau.%s\n", ColorPurple, ColorReset, ColorReset)
		}
		fmt.Printf("%s ╰───────────────────────────────────────────────────────────%s\n\n", ColorPurple, ColorReset)

		newToken := readPassword("👉 Nouveau Token : ")
		if newToken == "" {
			os.Exit(1)
		}
		saveToConfig("TOKEN", newToken)
		
		loginArgs = []string{"login", configMap["SERVER_URL"], "--token=" + configMap["TOKEN"]}
		if tlsOpt != "" {
			loginArgs = append(loginArgs, tlsOpt)
		}
		if err := runCmdSilently("oc", loginArgs...); err == nil {
			fmt.Printf("%s✅ Connexion réussie.%s\n", ColorGreen, ColorReset)
			runCmdSilently("oc", "project", configMap["DEFAULT_NAMESPACE"])
		} else {
			fmt.Printf("%s❌ Échec critique.%s\n", ColorRed, ColorReset)
			os.Exit(1)
		}
	}
}

func showDisclaimer() {
	if configMap["DISCLAIMER_ACCEPTED"] != "true" {
		clearScreen()
		sep := strings.Repeat("=", 92)
		fmt.Printf("%s%s%s\n", ColorRed, sep, ColorReset)
		fmt.Printf("%s%s                                ⚠️  AVERTISSEMENT LÉGAL ⚠️%s\n", ColorBold, ColorRed, ColorReset)
		fmt.Printf("%s%s%s\n\n", ColorRed, sep, ColorReset)

		fmt.Printf("%s Cet outil n'est NI un produit officiel SAS Institute, NI un produit officiel OpenShift.%s\n\n", ColorCyan, ColorReset)
		fmt.Printf(" Il s'agit d'une %sboîte à outils non officielle%s conçue pour faciliter la gestion et\n", ColorBold, ColorReset)
		fmt.Printf(" l'administration d'un environnement SAS Viya 4 sur un cluster OpenShift.\n\n")

		fmt.Printf("%s 🛑 Responsabilité :%s\n", ColorYellow, ColorReset)
		fmt.Printf(" L'utilisation de ce script se fait à vos propres risques. Les auteurs déclinent toute\n")
		fmt.Printf(" responsabilité en cas de mauvaise manipulation, de coupure de service ou de perte de\n")
		fmt.Printf(" données sur votre cluster.\n\n")

		fmt.Printf("%s 💡 Conseil de sécurité :%s\n", ColorPurple, ColorReset)
		fmt.Printf(" La gestion des Profils (via %s--profile%s) permet de cloisonner vos configurations.\n", ColorBold, ColorReset)
		fmt.Printf(" Utilisez-les systématiquement pour éviter d'exécuter des actions critiques en\n")
		fmt.Printf(" PRODUCTION par inadvertance !\n\n")

		fmt.Printf("%s%s%s\n", ColorRed, sep, ColorReset)

		readInput(" 👉 Appuyez sur Entrée pour accepter ces conditions et continuer...", "")
		saveToConfig("DISCLAIMER_ACCEPTED", "true")
	}
}

func showHelp() {
	fmt.Printf("%s", ColorCyan)
	fmt.Println("  ____       _      ____   __     __ ___  __   __     _       _  _     ___   ____   ____  ")
	fmt.Println(" / ___|     / \\    / ___|  \\ \\   / / |_ _| \\ \\ / /   / \\     | || |   / _ \\ |  _ \\ / ___| ")
	fmt.Println(" \\___ \\    / _ \\   \\___ \\   \\ \\ / /   | |   \\ V /   / _ \\    | || |_ | | | || |_) |\\___ \\ ")
	fmt.Println("  ___) |  / ___ \\   ___) |   \\ V /    | |    | |   / ___ \\   |__   _|| |_| ||  __/  ___) |")
	fmt.Println(" |____/  /_/   \\_\\ |____/     \\_/    |___|   |_|  /_/   \\_\\     |_|   \\___/ |_|    |____/ ")
	fmt.Printf("%s", ColorReset)

	sep := strings.Repeat("=", 92)
	fmt.Printf("%s%s%s%s\n", ColorBold, ColorBlue, sep, ColorReset)
	fmt.Printf("%s   SAS VIYA 4 OPS - Boîte à outils%s\n", ColorBold, ColorReset)
	fmt.Println("   (c) Nicolas Housset | https://github.com/nhousset/Viya4OC/ | https://nicolas-housset.fr/")
	fmt.Printf("%s%s%s%s\n\n", ColorBold, ColorBlue, sep, ColorReset)

	fmt.Printf("%sUsage:%s\n", ColorBold, ColorReset)
	fmt.Println("  ./viya [OPTIONS]")
	fmt.Println("")
	fmt.Printf("%sOptions:%s\n", ColorBold, ColorReset)
	fmt.Printf("  %s-h, --help%s           Affiche cet écran d'aide.\n", ColorCyan, ColorReset)
	fmt.Printf("  %s-p, --profile <nom>%s  Charge un profil spécifique (ex: -p PROD charge config-prod.env).\n", ColorCyan, ColorReset)
	fmt.Println("                       Si le fichier n'existe pas, un nouveau profil est configuré.")
	fmt.Printf("  %s--cmd <script.sh>%s    Exécute directement un script sans passer par le menu.\n\n", ColorCyan, ColorReset)

	fmt.Printf("%sExemples:%s\n", ColorBold, ColorReset)
	fmt.Printf("  ./viya                        %s# Lance le menu avec le profil par défaut%s\n", ColorCyan, ColorReset)
	fmt.Printf("  ./viya --profile PROD         %s# Lance le menu avec la config config-prod.env%s\n", ColorCyan, ColorReset)
	fmt.Printf("  ./viya --cmd check_status.sh  %s# Exécute directement un script%s\n\n", ColorCyan, ColorReset)
}

func printProdBanner() {
	if strings.Contains(strings.ToLower(configMap["ENV_TYPE"]), "prod") {
		fmt.Printf("%s%s+------------------------------------ ⚠️  PRODUCTION ⚠️  ------------------------------------+%s\n", ColorBold, ColorRed, ColorReset)
	}
}

func showConfigInfo() {
	clearScreen()
	sep := strings.Repeat("=", 92)
	sepDash := strings.Repeat("-", 92)

	fmt.Printf("%s%s%s\n", ColorBlue, sep, ColorReset)
	fmt.Printf("%s   🔧 Informations de Configuration%s\n", ColorBold, ColorReset)
	fmt.Printf("%s%s%s\n", ColorBlue, sep, ColorReset)

	fmt.Printf("   Profil Actif : %s%s%s\n", ColorCyan, profileName, ColorReset)
	fmt.Printf("   Fichier      : %s%s%s\n", ColorYellow, configFile, ColorReset)
	fmt.Printf("%s%s%s\n", ColorBlue, sepDash, ColorReset)

	if b, err := os.ReadFile(configFile); err == nil {
		for _, line := range strings.Split(string(b), "\n") {
			line = strings.TrimSpace(line)
			if line == "" {
				continue
			}
			if strings.HasPrefix(line, "export TOKEN=") {
				fmt.Printf(" export %sTOKEN%s=\"%s*** MASQUÉ ***%s\"\n", ColorCyan, ColorReset, ColorGreen, ColorReset)
			} else if strings.HasPrefix(line, "export ") {
				parts := strings.SplitN(strings.TrimPrefix(line, "export "), "=", 2)
				if len(parts) == 2 {
					fmt.Printf(" export %s%s%s=%s%s%s\n", ColorCyan, parts[0], ColorReset, ColorYellow, parts[1], ColorReset)
				}
			} else {
				fmt.Printf(" %s\n", line)
			}
		}
	} else {
		fmt.Printf(" %sFichier introuvable ou vide.%s\n", ColorRed, ColorReset)
	}

	fmt.Printf("\n%s%s%s\n", ColorBlue, sep, ColorReset)
	fmt.Printf("%s   📦 Version du client OpenShift (oc)%s\n", ColorBold, ColorReset)
	fmt.Printf("%s%s%s\n", ColorBlue, sep, ColorReset)

	cmd := exec.Command("oc", "version", "--client")
	cmd.Stdout = os.Stdout
	cmd.Stderr = os.Stderr
	if err := cmd.Run(); err != nil {
		fmt.Printf(" %sBinaire 'oc' introuvable dans le PATH.%s\n", ColorRed, ColorReset)
	}
	fmt.Printf("%s%s%s\n\n", ColorBlue, sep, ColorReset)

	readInput("👉 Appuyez sur Entrée pour revenir au menu...", "")
}

func clearScreen() {
	fmt.Print("\033[H\033[2J")
}

func getRunningCount() string {
	if dryRun {
		return "[DRY-RUN]"
	}
	cmd := exec.Command("oc", "get", "pods", "-n", configMap["DEFAULT_NAMESPACE"], "--field-selector=status.phase=Running", "--no-headers")
	out, err := cmd.Output()
	if err != nil {
		return "0"
	}
	lines := strings.Split(strings.TrimSpace(string(out)), "\n")
	if len(lines) == 1 && lines[0] == "" {
		return "0"
	}
	return strconv.Itoa(len(lines))
}

func getResources() (string, string) {
	if dryRun {
		return "N/A", "N/A"
	}
	out, err := exec.Command("oc", "describe", "resourcequota", "-n", configMap["DEFAULT_NAMESPACE"]).Output()
	if err != nil {
		return "N/A", "N/A"
	}
	
	cpu := "N/A"
	mem := "N/A"
	
	for _, line := range strings.Split(string(out), "\n") {
		fields := strings.Fields(line)
		if len(fields) >= 3 {
			if fields[0] == "limits.cpu" {
				used := fields[1]
				hard := fields[2]
				
				usedCore := parseCPU(used)
				hardCore := parseCPU(hard)
				pct := 0.0
				if hardCore > 0 {
					pct = (usedCore / hardCore) * 100
				}
				cpu = fmt.Sprintf("%.3f/%s (%.2f%%)", usedCore, hard, pct)
			}
			if fields[0] == "limits.memory" {
				used := fields[1]
				hard := fields[2]
				
				usedBytes := parseMem(used)
				hardBytes := parseMem(hard)
				pct := 0.0
				if hardBytes > 0 {
					pct = (usedBytes / hardBytes) * 100
				}
				mem = fmt.Sprintf("%.0f/%.0f (%.2f%%)", usedBytes, hardBytes, pct)
			}
		}
	}
	
	return cpu, mem
}

func parseCPU(s string) float64 {
	if strings.HasSuffix(s, "m") {
		v, _ := strconv.ParseFloat(strings.TrimSuffix(s, "m"), 64)
		return v / 1000.0
	}
	v, _ := strconv.ParseFloat(s, 64)
	return v
}

func parseMem(s string) float64 {
	s = strings.TrimSpace(s)
	multiplier := 1.0
	if strings.HasSuffix(s, "Ti") {
		multiplier = 1024 * 1024 * 1024 * 1024
		s = strings.TrimSuffix(s, "Ti")
	} else if strings.HasSuffix(s, "Gi") {
		multiplier = 1024 * 1024 * 1024
		s = strings.TrimSuffix(s, "Gi")
	} else if strings.HasSuffix(s, "Mi") {
		multiplier = 1024 * 1024
		s = strings.TrimSuffix(s, "Mi")
	} else if strings.HasSuffix(s, "Ki") {
		multiplier = 1024
		s = strings.TrimSuffix(s, "Ki")
	}
	v, _ := strconv.ParseFloat(s, 64)
	return v * multiplier
}

func mEcho(text string, isProd bool, iw int) {
	if isProd {
		cleanText := regexp.MustCompile(`\x1b\[[0-9;]*m`).ReplaceAllString(text, "")
		pad := iw - len(cleanText)
		if pad < 0 {
			pad = 0
		}
		fmt.Printf("%s|%s%s%s%s|%s\n", ColorRed, ColorReset, text, strings.Repeat(" ", pad), ColorRed, ColorReset)
	} else {
		fmt.Println(text)
	}
}

func showMenu() {
	doLogin()
	
	runningCount := getRunningCount()
	resCPU, resMem := getResources()
	
	isProd := strings.Contains(strings.ToLower(configMap["ENV_TYPE"]), "prod")
	iw := 92
	
	clearScreen()
	
	if isProd {
		fmt.Printf("%s+%s+%s\n", ColorRed, strings.Repeat("-", iw), ColorReset)
		mEcho(fmt.Sprintf(" %s%s!!! ATTENTION - ENVIRONNEMENT DE PRODUCTION !!!%s", ColorBold, ColorRed, ColorReset), isProd, iw)
	}
	
	mEcho(fmt.Sprintf("%s  ____       _      ____   __     __ ___  __   __     _       _  _     ___   ____   ____  %s", ColorCyan, ColorReset), isProd, iw)
	mEcho(fmt.Sprintf("%s / ___|     / \\    / ___|  \\ \\   / / |_ _| \\ \\ / /   / \\     | || |   / _ \\ |  _ \\ / ___| %s", ColorCyan, ColorReset), isProd, iw)
	mEcho(fmt.Sprintf("%s \\___ \\    / _ \\   \\___ \\   \\ \\ / /   | |   \\ V /   / _ \\    | || |_ | | | || |_) |\\___ \\ %s", ColorCyan, ColorReset), isProd, iw)
	mEcho(fmt.Sprintf("%s  ___) |  / ___ \\   ___) |   \\ V /    | |    | |   / ___ \\   |__   _|| |_| ||  __/  ___) |%s", ColorCyan, ColorReset), isProd, iw)
	mEcho(fmt.Sprintf("%s |____/  /_/   \\_\\ |____/     \\_/    |___|   |_|  /_/   \\_\\     |_|   \\___/ |_|    |____/ %s", ColorCyan, ColorReset), isProd, iw)
	
	if isProd {
		fmt.Printf("%s+%s+%s\n", ColorRed, strings.Repeat("=", iw), ColorReset)
	} else {
		fmt.Printf("%s%s%s\n", ColorBlue, strings.Repeat("=", iw), ColorReset)
	}
	
	mEcho(fmt.Sprintf(" %s  SAS VIYA 4 OPS - Boîte à outils%s", ColorBold, ColorReset), isProd, iw)
	mEcho("   (c) Nicolas Housset | https://github.com/nhousset/Viya4OC/ | https://nicolas-housset.fr/", isProd, iw)
	
	if isProd {
		fmt.Printf("%s+%s+%s\n", ColorRed, strings.Repeat("=", iw), ColorReset)
	} else {
		fmt.Printf("%s%s%s\n", ColorBlue, strings.Repeat("=", iw), ColorReset)
	}
	
	fmt.Printf(" Namespace : %s%s%s\n", ColorCyan, configMap["DEFAULT_NAMESPACE"], ColorReset)
	fmt.Printf(" Profil    : %s%s%s (%s)\n", ColorPurple, profileName, ColorReset, configFile)
	fmt.Printf(" Statut    : %sConnecté%s | Pods: %s%s%s | CPU: %s%s%s | RAM: %s%s%s\n", ColorGreen, ColorReset, ColorYellow, runningCount, ColorReset, ColorPurple, resCPU, ColorReset, ColorPurple, resMem, ColorReset)
	fmt.Printf("%s%s%s\n", ColorBlue, strings.Repeat("-", iw), ColorReset)
	
	os.MkdirAll(cmdDir, 0755)
	
	files, _ := filepath.Glob(filepath.Join(cmdDir, "*.sh"))
	sort.Strings(files)
	
	if len(files) == 0 {
		fmt.Printf("%s   (Aucun plugin trouvé)%s\n", ColorRed, ColorReset)
	} else {
		for i, f := range files {
			title := filepath.Base(f)
			if b, err := os.ReadFile(f); err == nil {
				for _, line := range strings.Split(string(b), "\n") {
					if strings.HasPrefix(line, "# TITLE:") {
						title = strings.TrimSpace(strings.TrimPrefix(line, "# TITLE:"))
						break
					}
				}
			}
			fmt.Printf(" %s%s%d)%s %s\n", ColorBold, ColorCyan, i+1, ColorReset, title)
		}
	}
	
	fmt.Printf("%s%s%s\n", ColorBlue, strings.Repeat("-", iw), ColorReset)
	fmt.Printf(" %s%s99)%s Informations de Configuration & Version OC\n", ColorBold, ColorCyan, ColorReset)
	fmt.Printf("%s%s%s\n", ColorBlue, strings.Repeat("-", iw), ColorReset)
	fmt.Printf(" %sq)%s Quitter & Logout      %sx)%s Quitter (Garder session)\n", ColorRed, ColorReset, ColorRed, ColorReset)
	fmt.Printf("%s%s%s\n", ColorBlue, strings.Repeat("=", iw), ColorReset)
	
	choice := readInput("👉 Votre choix ? ", "")
	
	switch choice {
	case "q":
		if !dryRun {
			runCmdSilently("oc", "logout")
		}
		os.Exit(0)
	case "x":
		fmt.Println("Bye.")
		os.Exit(0)
	case "99":
		showConfigInfo()
		return
	}
	
	idx, err := strconv.Atoi(choice)
	if err != nil || idx < 1 || idx > len(files) {
		fmt.Printf("%s❌ Choix invalide.%s\n", ColorRed, ColorReset)
		return
	}
	
	selectedScript := files[idx-1]
	fmt.Printf("\n%s🚀 Lancement : %s%s\n", ColorYellow, filepath.Base(selectedScript), ColorReset)
	fmt.Printf("%s--------------------------------------------------------------------------------------------%s\n", ColorBlue, ColorReset)
	
	printProdBanner()
	
	os.Chmod(selectedScript, 0755)
	
	os.Setenv("DEFAULT_NAMESPACE", configMap["DEFAULT_NAMESPACE"])
	os.Setenv("AUDIT_OUT_DIR", configMap["AUDIT_OUT_DIR"])
	if dryRun {
		os.Setenv("DRY_RUN", "true")
	} else {
		os.Setenv("DRY_RUN", "false")
	}
	
	runScript(selectedScript)
	
	fmt.Printf("%s--------------------------------------------------------------------------------------------%s\n", ColorBlue, ColorReset)
	readInput("Appuyez sur Entrée pour revenir au menu...", "")
}

func runScript(scriptPath string) {
	cmd := exec.Command("bash", scriptPath)
	cmd.Stdout = os.Stdout
	cmd.Stderr = os.Stderr
	cmd.Stdin = os.Stdin
	cmd.Run()
}
