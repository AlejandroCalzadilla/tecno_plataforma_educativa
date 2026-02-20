<template>
    <AppLayout :title="`Sesión: ${session?.servicio?.nombre || 'Cargando...'}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalles de la Sesión
                </h2>
                <Link href="/sesiones" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                    ← Volver al Calendario
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div v-if="loading" class="p-6">
                        <div class="animate-pulse">
                            <div class="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
                            <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                        </div>
                    </div>

                    <div v-else-if="session" class="p-6">
                        <!-- Información principal de la sesión -->
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ session.servicio.nombre }}</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Información básica -->
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Fecha y Hora</label>
                                        <p class="text-lg">{{ formatDate(session.fecha) }}</p>
                                        <p class="text-gray-600">{{ session.hora_inicio }} - {{ session.hora_fin }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                                        <span :class="[
                                            'inline-flex px-3 py-1 text-sm font-semibold rounded-full',
                                            session.status === 'programada' ? 'bg-blue-100 text-blue-800' :
                                            session.status === 'completada' ? 'bg-green-100 text-green-800' :
                                            session.status === 'cancelada' ? 'bg-red-100 text-red-800' :
                                            'bg-gray-100 text-gray-800'
                                        ]">
                                            {{ session.status.charAt(0).toUpperCase() + session.status.slice(1) }}
                                        </span>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tipo de Servicio</label>
                                        <p class="text-gray-900">{{ session.servicio.categoria_nivel.nombre }}</p>
                                    </div>
                                </div>

                                <!-- Participantes -->
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tutor</label>
                                        <p class="text-gray-900">{{ session.tutor.user.name }}</p>
                                        <p class="text-sm text-gray-600">{{ session.tutor.user.email }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Alumno</label>
                                        <p class="text-gray-900">{{ session.alumno.user.name }}</p>
                                        <p class="text-sm text-gray-600">{{ session.alumno.user.email }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Link virtual -->
                        <div v-if="session.link_virtual" class="mb-8">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                <h4 class="text-lg font-semibold text-blue-900 mb-4">Acceso a la Sesión Virtual</h4>
                                <p class="text-blue-800 mb-4">
                                    La sesión está programada para comenzar pronto. Haz clic en el botón para unirte.
                                </p>
                                <button @click="joinSession"
                                        class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                    </svg>
                                    Unirme a la Sesión
                                </button>
                                <p class="text-sm text-blue-600 mt-2">
                                    Link: <a :href="session.link_virtual" target="_blank" class="underline hover:no-underline">{{ session.link_virtual }}</a>
                                </p>
                            </div>
                        </div>

                        <!-- Información adicional -->
                        <div v-if="session.notas" class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Notas de la Sesión</h4>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <p class="text-gray-700 whitespace-pre-wrap">{{ session.notas }}</p>
                            </div>
                        </div>

                        <!-- Historial de informes -->
                        <div v-if="session.informes && session.informes.length > 0" class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Informes de Clase</h4>
                            <div class="space-y-4">
                                <div v-for="informe in session.informes" :key="informe.id"
                                     class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h5 class="font-semibold">{{ informe.titulo }}</h5>
                                        <span class="text-sm text-gray-500">{{ formatDate(informe.fecha_creacion) }}</span>
                                    </div>
                                    <p class="text-gray-700">{{ informe.contenido }}</p>
                                    <div class="mt-2 flex items-center space-x-4 text-sm text-gray-600">
                                        <span>Progreso: {{ informe.progreso }}%</span>
                                        <span>Asistencia: {{ informe.asistencia ? 'Presente' : 'Ausente' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="border-t pt-6">
                            <div class="flex space-x-4">
                                <Link :href="route('sesiones.index')"
                                      class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                                    Volver al Calendario
                                </Link>

                                <!-- Acciones específicas por rol -->
                                <button v-if="canEditSession"
                                        @click="editSession"
                                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                                    Editar Sesión
                                </button>

                                <button v-if="canCancelSession && session.status === 'programada'"
                                        @click="cancelSession"
                                        class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                    Cancelar Sesión
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-6">
                        <div class="text-center">
                            <p class="text-gray-500">No se pudo cargar la información de la sesión.</p>
                            <Link href="/sesiones" class="text-blue-600 hover:text-blue-800 mt-4 inline-block">
                                Volver al calendario
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

// Props
const props = defineProps({
    session: Object
})

// Datos reactivos
const loading = ref(false)
const session = ref(props.session)

// Computed properties
const canEditSession = computed(() => {
    const user = usePage().props.auth.user
    return user?.is_tutor && session.value?.tutor?.id === user?.tutor?.id
})

const canCancelSession = computed(() => {
    const user = usePage().props.auth.user
    return (user?.is_tutor && session.value?.tutor?.id === user?.tutor?.id) ||
           (user?.is_alumno && session.value?.alumno?.id === user?.alumno?.id)
})

// Métodos
const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const joinSession = () => {
    if (session.value?.link_virtual) {
        window.open(session.value.link_virtual, '_blank')
    }
}

const editSession = () => {
    // TODO: Implementar edición de sesión
    alert('Funcionalidad de edición próximamente disponible')
}

const cancelSession = () => {
    if (confirm('¿Estás seguro de que quieres cancelar esta sesión?')) {
        // TODO: Implementar cancelación de sesión
        alert('Funcionalidad de cancelación próximamente disponible')
    }
}

// Cargar datos si no se pasaron como props
onMounted(() => {
    if (!session.value) {
        // Si no hay sesión en props, intentar cargar desde la ruta
        const urlParams = new URLSearchParams(window.location.search)
        const sessionId = window.location.pathname.split('/').pop()

        if (sessionId) {
            loading.value = true
            fetch(`/sesiones/${sessionId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                session.value = data.session
            })
            .catch(error => {
                console.error('Error al cargar sesión:', error)
            })
            .finally(() => {
                loading.value = false
            })
        }
    }
})
</script>