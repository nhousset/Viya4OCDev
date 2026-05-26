package main
import (
	"fmt"
	"os"
	"os/exec"
	"regexp"
	"strings"
)
func main() {
	// Récupération de la variable d'environnement DEFAULT_NAMESPACE
	namespace := os.Getenv("DEFAULT_NAMESPACE")
	if namespace == "" {
		// Par défaut, si la variable n'est pas définie, on peut utiliser "default"
		namespace = "default"
	}
	fmt.Println("📊 État des Persistent Volume Claims (PVC) :")
	// Exécution de la commande "oc get pvc"
	cmdPVC := exec.Command("oc", "get", "pvc", "-n", namespace)
	cmdPVC.Stdout = os.Stdout
	cmdPVC.Stderr = os.Stderr
	if err := cmdPVC.Run(); err != nil {
		fmt.Printf("Erreur lors de l'exécution de 'oc get pvc' : %v\n", err)
	}
	fmt.Printf("\n⚠️  Événements récents liés à des erreurs de volumes (Warning) :\n")
	// Exécution de la commande "oc get events" avec le filtre Warning
	cmdEvents := exec.Command("oc", "get", "events", "-n", namespace, "--field-selector", "type=Warning")
	output, err := cmdEvents.CombinedOutput()
	if err != nil {
		fmt.Printf("Erreur lors de l'exécution de 'oc get events' : %v\n", err)
		// On continue quand même pour afficher ce qui a pu être récupéré
	}
	// Filtrage des événements (équivalent de grep -i -E "volume|pvc|storage")
	lines := strings.Split(string(output), "\n")
	var filteredEvents []string
	
	// Expression régulière insensible à la casse
	re := regexp.MustCompile(`(?i)(volume|pvc|storage)`)
	for _, line := range lines {
		if re.MatchString(line) {
			filteredEvents = append(filteredEvents, line)
		}
	}
	// Affichage du résultat
	if len(filteredEvents) == 0 {
		fmt.Println("✅ Aucun problème de volume récent détecté dans les événements.")
	} else {
		for _, event := range filteredEvents {
			fmt.Println(event)
		}
	}
}
