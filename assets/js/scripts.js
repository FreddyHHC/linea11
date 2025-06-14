document.addEventListener("DOMContentLoaded", () => {
  // Toggle sidebar
  const sidebarToggle = document.getElementById("sidebarToggle")
  const sidebar = document.getElementById("sidebar")
  const mainContent = document.querySelector(".main-content")
  const footer = document.querySelector(".footer")

  if (sidebarToggle && sidebar && mainContent) {
    sidebarToggle.addEventListener("click", () => {
      sidebar.classList.toggle("collapsed")
      mainContent.classList.toggle("expanded")
      if (footer) {
        footer.classList.toggle("expanded")
      }

      // Guardar estado en localStorage
      const sidebarCollapsed = sidebar.classList.contains("collapsed")
      localStorage.setItem("sidebarCollapsed", sidebarCollapsed)
    })

    // Restaurar estado del sidebar
    const sidebarCollapsed = localStorage.getItem("sidebarCollapsed") === "true"
    if (sidebarCollapsed) {
      sidebar.classList.add("collapsed")
      mainContent.classList.add("expanded")
      if (footer) {
        footer.classList.add("expanded")
      }
    }
  }

  // Responsive sidebar
  function checkWidth() {
    if (window.innerWidth < 768) {
      if (sidebar) {
        sidebar.classList.add("collapsed")
      }
      if (mainContent) {
        mainContent.classList.add("expanded")
      }
      if (footer) {
        footer.classList.add("expanded")
      }
    }
  }

  // Ejecutar al cargar
  checkWidth()

  // Ejecutar al cambiar tamaño de ventana
  window.addEventListener("resize", checkWidth)

  // Formatear campos RUN (12345678-9)
  const runInputs = document.querySelectorAll('input[name="run"]')
  runInputs.forEach((input) => {
    input.addEventListener("blur", function () {
      const value = this.value.replace(/[^\dkK]/g, "")
      if (value.length > 1) {
        const dv = value.slice(-1).toUpperCase()
        const run = value.slice(0, -1)
        this.value = run + "-" + dv
      }
    })
  })

  // Cerrar alertas automáticamente después de 5 segundos
  const alerts = document.querySelectorAll(".alert:not(.alert-danger)")
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.classList.add("fade")
      setTimeout(() => {
        alert.remove()
      }, 500)
    }, 5000)
  })
})
