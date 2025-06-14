"use client"

export default function HomePage() {
  return (
    <main className="flex min-h-screen items-center justify-center p-6">
      <div className="text-center space-y-4">
        <h1 className="text-2xl font-semibold">TaxiPack V2.0</h1>
        <p className="text-sm text-gray-500">Seleccione una opción para continuar</p>
        <div className="flex flex-col gap-4 sm:flex-row sm:justify-center">
          <a
            href="https://www.linea11calama.cl"
            className="rounded-md border border-blue-600 px-4 py-2 text-blue-600 hover:bg-blue-50"
          >
            Ir al Sitio Web
          </a>
          <a
            href="/login.php"
            className="rounded-md border border-green-600 px-4 py-2 text-green-600 hover:bg-green-50"
          >
            Iniciar Sesión
          </a>
        </div>
      </div>
    </main>
  )
}
