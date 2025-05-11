import { Chart } from "@/components/ui/chart"
// Gestion du mode sombre/clair
document.addEventListener("DOMContentLoaded", () => {
  // Initialisation du mode selon la préférence enregistrée
  const darkMode = localStorage.getItem("darkMode") === "true"
  if (darkMode) {
    document.documentElement.classList.add("dark")
  }

  // Fonction pour mettre à jour les graphiques selon le mode
  function updateChartsTheme(isDark) {
    const textColor = isDark ? "#e2e8f0" : "#4a5568"
    const gridColor = isDark ? "rgba(255, 255, 255, 0.1)" : "rgba(0, 0, 0, 0.1)"

    Chart.instances.forEach((chart) => {
      // Mise à jour des couleurs de texte
      if (chart.options.scales && chart.options.scales.y) {
        chart.options.scales.y.ticks.color = textColor
        chart.options.scales.y.grid.color = gridColor
      }

      if (chart.options.scales && chart.options.scales.x) {
        chart.options.scales.x.ticks.color = textColor
        chart.options.scales.x.grid.color = gridColor
      }

      if (chart.options.plugins && chart.options.plugins.legend) {
        chart.options.plugins.legend.labels.color = textColor
      }

      chart.update()
    })
  }

  // Écouteur pour le bouton de changement de mode
  const darkModeToggle = document.getElementById("darkModeToggle")
  if (darkModeToggle) {
    darkModeToggle.addEventListener("click", () => {
      const isDark = document.documentElement.classList.toggle("dark")
      localStorage.setItem("darkMode", isDark)
      updateChartsTheme(isDark)
    })
  }

  // Recherche avancée - toggle
  const advancedSearchToggle = document.getElementById("advancedSearchToggle")
  const advancedSearchPanel = document.getElementById("advancedSearchPanel")

  if (advancedSearchToggle && advancedSearchPanel) {
    advancedSearchToggle.addEventListener("click", () => {
      advancedSearchPanel.classList.toggle("hidden")
      const isExpanded = !advancedSearchPanel.classList.contains("hidden")
      advancedSearchToggle.setAttribute("aria-expanded", isExpanded)

      // Changer l'icône
      const icon = advancedSearchToggle.querySelector("i")
      if (isExpanded) {
        icon.classList.remove("fa-chevron-down")
        icon.classList.add("fa-chevron-up")
      } else {
        icon.classList.remove("fa-chevron-up")
        icon.classList.add("fa-chevron-down")
      }
    })
  }

  // Recherche en temps réel (optionnel)
  const searchInput = document.getElementById("q")
  let searchTimeout

  if (searchInput) {
    searchInput.addEventListener("input", () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        if (searchInput.value.length >= 3 || searchInput.value.length === 0) {
          document.querySelector("form").submit()
        }
      }, 500)
    })
  }

  // Animation des cartes de livres
  const bookCards = document.querySelectorAll(".book-card")
  bookCards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-5px)"
      this.style.boxShadow = "0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)"
    })

    card.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)"
      this.style.boxShadow = "0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)"
    })
  })
})

// Fonction pour exporter les résultats en CSV
function exportToCSV() {
  // Récupérer les paramètres de recherche actuels
  const urlParams = new URLSearchParams(window.location.search)
  urlParams.set("export", "csv")

  // Rediriger vers l'URL d'export
  window.location.href = "export.php?" + urlParams.toString()
}

// Fonction pour imprimer les résultats
function printResults() {
  window.print()
}
