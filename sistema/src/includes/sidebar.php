<aside class="sidebar text-white" id="sidebar" style="background-color: #052b38;">
    <div class="sidebar-content">
        <nav class="nav flex-column">
            <a class="nav-link <?php echo ($modulo == 'dashboard') ? 'active' : ''; ?>" href="?modulo=dashboard">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            
            <?php if (verificarPermiso('usuarios', 'ver', $_SESSION['user_perfil_id'], $pdo) || verificarPermiso('perfiles', 'ver', $_SESSION['user_perfil_id'], $pdo) || verificarPermiso('modulos', 'ver', $_SESSION['user_perfil_id'], $pdo) || verificarPermiso('permisos', 'ver', $_SESSION['user_perfil_id'], $pdo)): ?>
            <div class="nav-group">
                <h6 class="nav-group-title">Administración</h6>
                <?php if (verificarPermiso('usuarios', 'ver', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a class="nav-link <?php echo ($modulo == 'usuarios') ? 'active' : ''; ?>" href="?modulo=usuarios">
                    <i class="fas fa-users"></i> Usuarios
                </a>
                <?php endif; ?>
                <?php if (verificarPermiso('perfiles', 'ver', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a class="nav-link <?php echo ($modulo == 'perfiles') ? 'active' : ''; ?>" href="?modulo=perfiles">
                    <i class="fas fa-user-tag"></i> Perfiles
                </a>
                <?php endif; ?>
                <?php if (verificarPermiso('permisos', 'ver', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a class="nav-link <?php echo ($modulo == 'permisos') ? 'active' : ''; ?>" href="?modulo=permisos">
                    <i class="fas fa-key"></i> Permisos
                </a>
                <?php endif; ?>
                <?php if (verificarPermiso('modulos', 'ver', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a class="nav-link <?php echo ($modulo == 'modulos') ? 'active' : ''; ?>" href="?modulo=modulos">
                    <i class="fas fa-cubes"></i> Módulos
                </a>
                <?php endif; ?>
                <?php if (verificarPermiso('tipos_egresos', 'ver', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a class="nav-link <?php echo ($modulo == 'tipos_egresos') ? 'active' : ''; ?>" href="?modulo=tipos_egresos">
                    <i class="fas fa-money-bill-wave"></i> Tipos de Egresos
                </a>
                <?php endif; ?>
                <?php if (verificarPermiso('tipos_comprobantes', 'ver', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a class="nav-link <?php echo ($modulo == 'tipos_comprobantes') ? 'active' : ''; ?>" href="?modulo=tipos_comprobantes">
                    <i class="fas fa-file-invoice"></i> Tipos Comprobantes
                </a>
                <?php endif; ?>
                <?php if (verificarPermiso('tipos_ingresos', 'ver', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a class="nav-link <?php echo ($modulo == 'tipos_ingresos') ? 'active' : ''; ?>" href="?modulo=tipos_ingresos">
                    <i class="fas fa-money-check-alt"></i> Tipos de Ingresos
                </a>
                <?php endif; ?>
                <?php if (verificarPermiso('respaldos_documentos', 'ver', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a class="nav-link <?php echo ($modulo == 'respaldos_documentos') ? 'active' : ''; ?>" href="?modulo=respaldos_documentos">
                    <i class="fas fa-file-archive"></i> Respaldos Documentos
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Aquí irán los demás módulos según los permisos -->
        </nav>
    </div>
</aside>