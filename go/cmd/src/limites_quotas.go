package main

import (
	"fmt"
	"os"
	"os/exec"
	"strings"
)

// TITLE: Audit des Limites et Quotas (CPU/RAM)

// Exécute une commande et retourne la sortie sous forme de texte (pour la lire)
func runCmdOutput(name string, args ...string) string {
	cmd := exec.Command(name, args...)
	out, _ := cmd.CombinedOutput()
	return string(out)
}

// Exécute une commande interactive et affiche directement le résultat dans le terminal
func runCmdInteractive(name string, args ...string) {
	cmd := exec.Command(name, args...)
	cmd.Stdout = os.Stdout
	cmd.Stderr = os.Stderr
	cmd.Run()
}

func main() {
	// On récupère le namespace exporté par le menu principal viya
	namespace := os.Getenv("DEFAULT_NAMESPACE")
	if namespace == "" {
		namespace = "sas-viya" // Valeur par défaut au cas où
	}

	fmt.Println("\n=== [AUDIT DES LIMITES ET QUOTAS] ===")
	fmt.Printf("Namespace cible : %s\n", namespace)

	// 1. Vérification des ResourceQuotas
	fmt.Println("\n📊 Resource Quotas (Limites globales du projet) :")
	quotasOut := runCmdOutput("oc", "get", "resourcequotas", "-n", namespace, "--no-headers")
	
	if strings.TrimSpace(quotasOut) == "" {
		fmt.Println("✅ Aucun ResourceQuota défini sur ce namespace. Le projet n'est pas bridé globalement.")
	} else {
		runCmdInteractive("oc", "describe", "resourcequotas", "-n", namespace)
		fmt.Println("\n💡 Astuce : Si une ressource est proche de sa limite 'Hard', de nouveaux pods pourraient rester en 'Pending'.")
	}

	// 2. Vérification des LimitRanges
	fmt.Println("\n🎯 Limit Ranges (Règles par défaut par Pod/Conteneur) :")
	limitsOut := runCmdOutput("oc", "get", "limitranges", "-n", namespace, "--no-headers")
	
	if strings.TrimSpace(limitsOut) == "" {
		fmt.Println("✅ Aucun LimitRange défini sur ce namespace.")
	} else {
		runCmdInteractive("oc", "describe", "limitranges", "-n", namespace)
		fmt.Println("\n💡 Astuce : Les LimitRanges forcent des 'requests' et 'limits' sur les pods qui n'en déclarent pas.")
		fmt.Println("Dans SAS Viya, cela peut impacter le démarrage des sessions Compute si elles sont mal dimensionnées.")
	}

	fmt.Println("\n---------------------------------------------------")
}
