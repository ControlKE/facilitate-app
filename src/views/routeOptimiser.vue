<template>
  <v-container fluid class="route-page">
    <section class="route-shell">
      <header class="shell-header">
        <div>
          <p class="header-kicker">Care Planning</p>
          <h1>Route Optimiser</h1>
          <p class="header-copy">
            Add client addresses, group them into a run, fix the first call, and save a suggested visit order that reduces travel between calls.
          </p>
        </div>
        <div class="header-actions">
          <v-btn variant="outlined" color="primary" prepend-icon="mdi-refresh" :loading="loading" @click="loadBootstrap">
            Refresh
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="resetRunForm">
            New Run
          </v-btn>
        </div>
      </header>

      <div class="stats-grid">
        <v-card class="stat-card" elevation="0">
          <p>Total Clients</p>
          <h3>{{ stats.totalClients }}</h3>
        </v-card>
        <v-card class="stat-card" elevation="0">
          <p>Active Clients</p>
          <h3>{{ stats.activeClients }}</h3>
        </v-card>
        <v-card class="stat-card" elevation="0">
          <p>Saved Runs</p>
          <h3>{{ stats.savedRuns }}</h3>
        </v-card>
        <v-card class="stat-card" elevation="0">
          <p>Upcoming Runs</p>
          <h3>{{ stats.upcomingRuns }}</h3>
        </v-card>
      </div>

      <div class="editor-grid">
        <v-card class="panel-card" elevation="0">
          <v-card-title class="panel-title">
            Client Details / Address Entry
          </v-card-title>
          <v-card-text>
            <v-row dense>
              <v-col cols="12" md="6">
                <v-text-field v-model="clientForm.fullName" label="Full Name*" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="clientForm.preferredCallType"
                  :items="callTypeItems"
                  label="Preferred Call Type"
                  variant="outlined"
                  density="comfortable"
                  clearable
                ></v-select>
              </v-col>
              <v-col cols="12">
                <v-text-field v-model="clientForm.addressLine1" label="Address Line 1*" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-text-field v-model="clientForm.addressLine2" label="Address Line 2" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="clientForm.townCity" label="Town / City*" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="clientForm.county" label="County" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="clientForm.postcode" label="Postcode*" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="clientForm.areaZone" label="Area / Zone" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12" md="8">
                <div class="map-action-stack">
                  <div class="map-action-row">
                    <v-btn
                      color="primary"
                      variant="outlined"
                      prepend-icon="mdi-map-search-outline"
                      :disabled="!canOpenClientMap"
                      @click="openClientMap"
                    >
                      Find on Map
                    </v-btn>
                    <v-btn
                      variant="text"
                      color="primary"
                      prepend-icon="mdi-delete-outline"
                      :disabled="!hasClientCoordinates"
                      @click="clearClientCoordinates"
                    >
                      Clear Pin
                    </v-btn>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    Search the address, then drag the pin or click the map to confirm the visit coordinates.
                  </div>
                  <div class="map-chip-row">
                    <v-chip size="small" variant="outlined" :color="mapLookup.enabled ? 'primary' : undefined">
                      {{ mapLookup.enabled ? clientMapProviderLabel : 'Manual pin placement only' }}
                    </v-chip>
                    <v-chip v-if="hasClientCoordinates" size="small" variant="outlined">
                      Lat {{ formatCoordinate(clientForm.latitude) }}
                    </v-chip>
                    <v-chip v-if="hasClientCoordinates" size="small" variant="outlined">
                      Lng {{ formatCoordinate(clientForm.longitude) }}
                    </v-chip>
                    <v-chip v-if="clientCoordinatesNeedReview" size="small" variant="tonal" color="warning">
                      Address changed. Review pin.
                    </v-chip>
                  </div>
                </div>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="clientForm.latitude" label="Latitude" type="number" step="0.0000001" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="clientForm.longitude" label="Longitude" type="number" step="0.0000001" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="clientForm.notes" label="Notes" rows="3" variant="outlined" auto-grow></v-textarea>
              </v-col>
              <v-col cols="12">
                <v-switch
                  v-model="clientForm.isActive"
                  color="primary"
                  hide-details
                  label="Client is active and available for future runs"
                ></v-switch>
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions>
            <v-btn variant="text" @click="resetClientForm">Clear</v-btn>
            <v-spacer></v-spacer>
            <v-btn color="primary" :loading="savingClient" :disabled="!canSaveClient" @click="submitClient">
              {{ clientForm.id ? 'Save Client' : 'Add Client' }}
            </v-btn>
          </v-card-actions>
        </v-card>

        <v-card class="panel-card" elevation="0">
          <v-card-title class="panel-title">
            Create Run
          </v-card-title>
          <v-card-text>
            <v-row dense>
              <v-col cols="12" md="7">
                <v-text-field v-model="runForm.runName" label="Run Name*" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12" md="5">
                <v-text-field v-model="runForm.runDate" type="date" label="Date*" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="runForm.shiftLabel" label="Shift / Time Period*" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="runForm.assignedCarerAccountId"
                  :items="carerItems"
                  item-title="label"
                  item-value="id"
                  label="Assigned Carer (optional)"
                  variant="outlined"
                  density="comfortable"
                  clearable
                ></v-select>
              </v-col>
              <v-col cols="12">
                <v-autocomplete
                  v-model="runForm.clientIds"
                  :items="availableClientItems"
                  item-title="label"
                  item-value="id"
                  label="Selected Clients Included In Run*"
                  variant="outlined"
                  density="comfortable"
                  chips
                  closable-chips
                  multiple
                  @update:modelValue="handleClientSelectionChange"
                ></v-autocomplete>
              </v-col>
              <v-col cols="12">
                <v-select
                  v-model="runForm.firstCallClientId"
                  :items="firstCallItems"
                  item-title="label"
                  item-value="id"
                  label="Select First Call / Starting Point*"
                  variant="outlined"
                  density="comfortable"
                  :disabled="!firstCallItems.length"
                ></v-select>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="runForm.notes" label="Run Notes" rows="3" variant="outlined" auto-grow></v-textarea>
              </v-col>
            </v-row>

            <v-alert v-if="!clients.length" type="info" variant="tonal" class="mt-2">
              Add at least one client before creating a run.
            </v-alert>

            <v-alert v-else-if="isPreviewDirty" type="warning" variant="tonal" class="mt-2">
              Client selection or first call changed. Regenerate the route before saving.
            </v-alert>
          </v-card-text>
          <v-card-actions class="run-actions">
            <v-btn variant="text" @click="resetRunForm">Clear Run</v-btn>
            <v-spacer></v-spacer>
            <v-btn color="primary" variant="outlined" :loading="generatingRoute" :disabled="!canGenerateRoute" @click="generateRoute">
              Generate Route
            </v-btn>
            <v-btn color="primary" :loading="savingRun" :disabled="!canSaveRun" @click="saveRun">
              {{ runForm.id ? 'Save Changes' : 'Save Run' }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </div>

      <v-card class="panel-card" elevation="0">
        <v-card-title class="panel-title">
          Saved Clients Table
        </v-card-title>
        <v-card-text>
          <div class="filters-row">
            <v-text-field
              v-model="clientFilters.search"
              density="comfortable"
              variant="outlined"
              hide-details
              prepend-inner-icon="mdi-magnify"
              placeholder="Search by client name, postcode, address, or notes"
            ></v-text-field>
            <v-select
              v-model="clientFilters.status"
              :items="clientStatusItems"
              density="comfortable"
              variant="outlined"
              hide-details
              label="Status"
            ></v-select>
          </div>

          <div class="table-shell">
            <v-table density="comfortable">
              <thead>
                <tr>
                  <th>Client</th>
                  <th>Full Address</th>
                  <th>Postcode</th>
                  <th>Call Type</th>
                  <th>Status</th>
                  <th>Updated</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="client in filteredClients" :key="client.id">
                  <td>
                    <div class="font-weight-medium">{{ client.fullName }}</div>
                    <div class="text-caption text-medium-emphasis">{{ client.areaZone || 'No zone set' }}</div>
                  </td>
                  <td>{{ client.fullAddress || '-' }}</td>
                  <td>{{ client.postcode || '-' }}</td>
                  <td>{{ client.preferredCallType || '-' }}</td>
                  <td>
                    <v-chip size="small" variant="tonal" :color="client.isActive ? 'success' : 'error'">
                      {{ client.isActive ? 'Active' : 'Inactive' }}
                    </v-chip>
                  </td>
                  <td>{{ formatDateTime(client.updatedAt) }}</td>
                  <td class="text-right">
                    <v-btn icon="mdi-eye-outline" variant="text" size="small" color="primary" @click="openClientDetail(client)"></v-btn>
                    <v-btn icon="mdi-pencil-outline" variant="text" size="small" color="primary" @click="editClient(client)"></v-btn>
                    <v-btn icon="mdi-delete-outline" variant="text" size="small" color="error" @click="promptDeleteClient(client)"></v-btn>
                  </td>
                </tr>
                <tr v-if="!filteredClients.length">
                  <td colspan="7" class="empty-row">No matching clients found.</td>
                </tr>
              </tbody>
            </v-table>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="panel-card" elevation="0">
        <v-card-title class="panel-title panel-title-split">
          <span>Route Order Results</span>
          <div class="route-meta">
            <v-chip v-if="routePreview.optimisationMethod" size="small" variant="tonal" color="primary">
              {{ formatMethodLabel(routePreview.optimisationMethod) }}
            </v-chip>
            <v-chip v-if="routePreview.stops.length" size="small" variant="outlined">
              {{ routePreview.stops.length }} stops
            </v-chip>
          </div>
        </v-card-title>
        <v-card-text>
          <v-alert v-if="!routePreview.stops.length" type="info" variant="tonal">
            Generate a route to see the suggested stop order here.
          </v-alert>

          <template v-else>
            <div class="route-toolbar">
              <div class="text-body-2 text-medium-emphasis">
                First call remains fixed. Use the arrows to manually adjust the remaining stops before saving.
              </div>
              <div class="route-toolbar-actions">
                <v-btn size="small" variant="outlined" color="primary" :loading="generatingRoute" :disabled="!canGenerateRoute" @click="generateRoute">
                  Regenerate Route
                </v-btn>
              </div>
            </div>

            <div class="table-shell">
              <v-table density="comfortable">
                <thead>
                  <tr>
                    <th>Position</th>
                    <th>Client</th>
                    <th>Address</th>
                    <th>Postcode</th>
                    <th>Travel Segment</th>
                    <th>Notes</th>
                    <th class="text-right">Reorder</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(stop, index) in routePreview.stops" :key="`${stop.clientId}-${index}`">
                    <td>
                      <div class="font-weight-bold">{{ stop.routeOrder }}</div>
                      <v-chip v-if="stop.isFirstCall" size="x-small" color="primary" variant="tonal">First Call</v-chip>
                    </td>
                    <td>
                      <div class="font-weight-medium">{{ stop.fullName }}</div>
                      <div class="text-caption text-medium-emphasis">{{ stop.preferredCallType || 'Standard visit' }}</div>
                    </td>
                    <td>{{ stop.fullAddress || '-' }}</td>
                    <td>{{ stop.postcode || '-' }}</td>
                    <td>
                      <div>{{ stop.segmentLabel || 'Manual order' }}</div>
                      <div v-if="stop.segmentDistanceKm !== null" class="text-caption text-medium-emphasis">
                        {{ Number(stop.segmentDistanceKm).toFixed(1) }} km straight-line estimate
                      </div>
                    </td>
                    <td>{{ stop.notes || '-' }}</td>
                    <td class="text-right">
                      <v-btn
                        icon="mdi-arrow-up"
                        size="small"
                        variant="text"
                        color="primary"
                        :disabled="!canMoveStop(index, -1)"
                        @click="moveStop(index, -1)"
                      ></v-btn>
                      <v-btn
                        icon="mdi-arrow-down"
                        size="small"
                        variant="text"
                        color="primary"
                        :disabled="!canMoveStop(index, 1)"
                        @click="moveStop(index, 1)"
                      ></v-btn>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </template>
        </v-card-text>
      </v-card>

      <v-card class="panel-card" elevation="0">
        <v-card-title class="panel-title">
          Saved Runs
        </v-card-title>
        <v-card-text>
          <div class="filters-row">
            <v-text-field
              v-model="runFilters.search"
              density="comfortable"
              variant="outlined"
              hide-details
              prepend-inner-icon="mdi-magnify"
              placeholder="Search by run name, date, shift, first call, or carer"
            ></v-text-field>
            <v-text-field
              v-model="runFilters.date"
              type="date"
              density="comfortable"
              variant="outlined"
              hide-details
              label="Filter by date"
            ></v-text-field>
          </div>

          <div class="table-shell">
            <v-table density="comfortable">
              <thead>
                <tr>
                  <th>Run</th>
                  <th>Date</th>
                  <th>Shift</th>
                  <th>First Call</th>
                  <th>Assigned Carer</th>
                  <th>Stops</th>
                  <th>Updated</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="run in filteredRuns" :key="run.id">
                  <td>
                    <div class="font-weight-medium">{{ run.runName }}</div>
                    <div class="text-caption text-medium-emphasis">
                      {{ run.manualOverride ? 'Manual order saved' : formatMethodLabel(run.optimisationMethod) }}
                    </div>
                  </td>
                  <td>{{ formatDate(run.runDate) }}</td>
                  <td>{{ run.shiftLabel || '-' }}</td>
                  <td>{{ run.firstCallName || '-' }}</td>
                  <td>{{ run.assignedCarerName || '-' }}</td>
                  <td>{{ run.stopCount }}</td>
                  <td>{{ formatDateTime(run.updatedAt) }}</td>
                  <td class="text-right">
                    <v-btn icon="mdi-pencil-outline" variant="text" size="small" color="primary" :loading="loadingRunId === run.id" @click="editRun(run)"></v-btn>
                    <v-btn icon="mdi-delete-outline" variant="text" size="small" color="error" @click="promptDeleteRun(run)"></v-btn>
                  </td>
                </tr>
                <tr v-if="!filteredRuns.length">
                  <td colspan="8" class="empty-row">No saved runs found.</td>
                </tr>
              </tbody>
            </v-table>
          </div>
        </v-card-text>
      </v-card>
    </section>

    <v-dialog v-model="clientMapDialog" max-width="980">
      <v-card class="map-dialog-card">
        <v-toolbar color="primary" title="Find Client on Map">
          <template #append>
            <v-btn icon="mdi-close" color="white" variant="text" @click="clientMapDialog = false"></v-btn>
          </template>
        </v-toolbar>
        <v-card-text class="pt-4">
          <div class="map-dialog-copy">
            Use the client address to drop a suggested pin, then drag the marker or click the map to confirm the exact visit location. When Google Maps is available you can also switch between 2D and 3D views, rotate the map, and use Street View controls.
          </div>

          <div class="map-dialog-toolbar">
            <v-btn
              color="primary"
              variant="outlined"
              prepend-icon="mdi-magnify"
              :loading="clientMapLookupLoading"
              :disabled="!canSearchClientAddress"
              @click="searchClientAddressOnMap(true)"
            >
              Search Address
            </v-btn>

            <v-btn-toggle
              :model-value="clientMapType"
              mandatory
              density="comfortable"
              divided
              @update:modelValue="updateClientMapType"
            >
              <v-btn value="roadmap">Roadmap</v-btn>
              <v-btn value="satellite">Satellite</v-btn>
            </v-btn-toggle>

            <v-btn-toggle
              v-if="clientMapSupports3d"
              :model-value="clientMapViewMode"
              mandatory
              density="comfortable"
              divided
              @update:modelValue="setClientMapViewMode"
            >
              <v-btn value="2d">2D</v-btn>
              <v-btn value="3d">3D</v-btn>
            </v-btn-toggle>

            <v-btn
              v-if="clientMapSupports3d"
              variant="text"
              color="primary"
              prepend-icon="mdi-rotate-left"
              @click="rotateClientMap(-30)"
            >
              Rotate Left
            </v-btn>

            <v-btn
              v-if="clientMapSupports3d"
              variant="text"
              color="primary"
              prepend-icon="mdi-rotate-right"
              @click="rotateClientMap(30)"
            >
              Rotate Right
            </v-btn>

            <v-btn
              variant="text"
              color="primary"
              prepend-icon="mdi-crosshairs-gps"
              @click="recenterClientMap"
            >
              Recenter
            </v-btn>

            <v-btn
              v-if="clientMapExternalUrl"
              variant="text"
              color="primary"
              prepend-icon="mdi-open-in-new"
              :href="clientMapExternalUrl"
              target="_blank"
              rel="noopener noreferrer"
            >
              Open in Google Maps
            </v-btn>

            <v-chip size="small" variant="outlined">Lat {{ formatCoordinate(clientMapDraft.latitude) }}</v-chip>
            <v-chip size="small" variant="outlined">Lng {{ formatCoordinate(clientMapDraft.longitude) }}</v-chip>
          </div>

          <div class="map-dialog-address">
            {{ clientLookupAddress || 'Start by filling in the client address fields above.' }}
          </div>

          <v-alert v-if="clientMapError" type="error" variant="tonal" class="mb-3">
            {{ clientMapError }}
          </v-alert>
          <v-alert v-else-if="clientMapInfo" type="info" variant="tonal" class="mb-3">
            {{ clientMapInfo }}
          </v-alert>

          <div class="map-chip-row map-chip-row-tight">
            <v-chip size="small" variant="outlined" :color="canUseGoogleClientMap ? 'primary' : undefined">
              {{ clientMapProviderLabel }}
            </v-chip>
            <v-chip v-if="clientMapDraft.formattedAddress" size="small" variant="outlined">
              {{ clientMapDraft.formattedAddress }}
            </v-chip>
            <v-chip v-if="clientMapDraft.locationType" size="small" variant="outlined" color="primary">
              {{ formatLocationType(clientMapDraft.locationType) }}
            </v-chip>
            <v-chip v-if="clientMapDraft.partialMatch" size="small" variant="tonal" color="warning">
              Approximate match
            </v-chip>
            <v-chip v-if="clientMapSupports3d" size="small" variant="outlined">
              {{ clientMapViewMode === '3d' ? `3D heading ${Math.round(clientMapHeading)}°` : '2D view' }}
            </v-chip>
          </div>

          <div ref="clientMapCanvas" class="route-client-map"></div>
        </v-card-text>
        <v-card-actions>
          <v-btn variant="text" @click="clientMapDialog = false">Cancel</v-btn>
          <v-spacer></v-spacer>
          <v-btn color="primary" :disabled="!clientMapHasDraftCoordinates" @click="applyClientMapCoordinates">
            Use Pin Location
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="clientDetailDialog" max-width="620">
      <v-card>
        <v-toolbar color="primary" title="Client Details"></v-toolbar>
        <v-card-text class="pt-4">
          <div v-if="selectedClient" class="detail-grid">
            <div>
              <strong>Name</strong>
              <p>{{ selectedClient.fullName }}</p>
            </div>
            <div>
              <strong>Status</strong>
              <p>{{ selectedClient.isActive ? 'Active' : 'Inactive' }}</p>
            </div>
            <div class="detail-span">
              <strong>Address</strong>
              <p>{{ selectedClient.fullAddress || '-' }}</p>
            </div>
            <div>
              <strong>Preferred Call Type</strong>
              <p>{{ selectedClient.preferredCallType || '-' }}</p>
            </div>
            <div>
              <strong>Area / Zone</strong>
              <p>{{ selectedClient.areaZone || '-' }}</p>
            </div>
            <div>
              <strong>Coordinates</strong>
              <p>
                {{ selectedClient.latitude !== null && selectedClient.longitude !== null
                  ? `${formatCoordinate(selectedClient.latitude)}, ${formatCoordinate(selectedClient.longitude)}`
                  : '-' }}
              </p>
            </div>
            <div class="detail-span">
              <strong>Notes</strong>
              <p>{{ selectedClient.notes || '-' }}</p>
            </div>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="clientDetailDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteClientDialog" max-width="520">
      <v-card>
        <v-card-title>Delete Client</v-card-title>
        <v-card-text>
          Delete <strong>{{ deleteClientTarget?.fullName }}</strong>? This cannot be undone if the client is not already linked to a saved run.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="deleteClientDialog = false">Cancel</v-btn>
          <v-btn color="error" :loading="deletingClient" @click="deleteClientConfirmed">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteRunDialog" max-width="520">
      <v-card>
        <v-card-title>Delete Run</v-card-title>
        <v-card-text>
          Delete <strong>{{ deleteRunTarget?.runName }}</strong>? The saved route order and linked stops will be removed.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="deleteRunDialog = false">Cancel</v-btn>
          <v-btn color="error" :loading="deletingRun" @click="deleteRunConfirmed">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :timeout="3600" :color="snackbar.type" location="top right">
      {{ snackbar.text }}
      <template #actions>
        <v-btn color="white" variant="text" @click="snackbar.show = false">Close</v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script>
import {
  deleteRouteClient,
  deleteRouteRun,
  describeRouteOptimiserError,
  fetchRouteOptimiserBootstrap,
  fetchRouteRun,
  generateOptimisedRoute,
  lookupRouteClientAddress,
  saveRouteClient,
  saveRouteRun,
} from '../services/routeOptimiserApi';
import { loadGoogleMapsApi } from '../utils/googleMapsLoader';
import { createLeafletTileLayer, loadLeafletAssets } from '../utils/leafletMap';

const DEFAULT_STATS = {
  totalClients: 0,
  activeClients: 0,
  inactiveClients: 0,
  savedRuns: 0,
  manualOverrideRuns: 0,
  upcomingRuns: 0,
};

const CALL_TYPE_ITEMS = [
  'Medication',
  'Personal Care',
  'Welfare Check',
  'Companionship',
  'Complex Care',
  'Night Call',
];

const DEFAULT_CLIENT_MAP = Object.freeze({
  latitude: 52.4056402,
  longitude: -1.5236883,
  zoom: 16,
  type: 'roadmap',
});

const todayIso = () => new Date().toISOString().slice(0, 10);

const emptyMapLookup = () => ({
  enabled: false,
  provider: 'manual_pin_only',
  country: 'GB',
});

const emptyClientMapDraft = () => ({
  latitude: null,
  longitude: null,
  formattedAddress: '',
  placeId: '',
  locationType: '',
  partialMatch: false,
});

const emptyClientForm = () => ({
  id: 0,
  fullName: '',
  addressLine1: '',
  addressLine2: '',
  townCity: '',
  county: '',
  postcode: '',
  notes: '',
  preferredCallType: '',
  areaZone: '',
  latitude: '',
  longitude: '',
  isActive: true,
});

const emptyRunForm = () => ({
  id: 0,
  runName: '',
  runDate: todayIso(),
  shiftLabel: 'Morning Run',
  assignedCarerAccountId: null,
  notes: '',
  clientIds: [],
  firstCallClientId: null,
});

export default {
  name: 'RouteOptimiser',
  data() {
    return {
      loading: false,
      savingClient: false,
      deletingClient: false,
      generatingRoute: false,
      savingRun: false,
      deletingRun: false,
      loadingRunId: 0,

      clients: [],
      runs: [],
      carers: [],
      stats: { ...DEFAULT_STATS },
      mapLookup: emptyMapLookup(),

      clientFilters: {
        search: '',
        status: 'all',
      },
      runFilters: {
        search: '',
        date: '',
      },

      clientForm: emptyClientForm(),
      runForm: emptyRunForm(),
      routePreview: {
        stops: [],
        optimisationMethod: '',
        summary: null,
      },
      previewSignature: '',

      selectedClient: null,
      clientDetailDialog: false,
      deleteClientDialog: false,
      deleteClientTarget: null,
      deleteRunDialog: false,
      deleteRunTarget: null,
      clientMapDialog: false,
      clientMapLookupLoading: false,
      clientMapError: '',
      clientMapInfo: '',
      clientMapDraft: emptyClientMapDraft(),
      clientMapCenter: {
        latitude: DEFAULT_CLIENT_MAP.latitude,
        longitude: DEFAULT_CLIENT_MAP.longitude,
      },
      clientMapZoom: DEFAULT_CLIENT_MAP.zoom,
      clientMapType: DEFAULT_CLIENT_MAP.type,
      clientMapConfirmedSignature: '',
      clientMapViewMode: '2d',
      clientMapHeading: 0,
      clientMapGoogleFailed: false,

      snackbar: {
        show: false,
        text: '',
        type: 'error',
      },
    };
  },
  computed: {
    callTypeItems() {
      return CALL_TYPE_ITEMS;
    },
    clientStatusItems() {
      return [
        { title: 'All Clients', value: 'all' },
        { title: 'Active Only', value: 'active' },
        { title: 'Inactive Only', value: 'inactive' },
      ];
    },
    availableClientItems() {
      return [...this.clients]
        .filter((client) => client.isActive || this.runForm.clientIds.includes(client.id))
        .sort((a, b) => a.fullName.localeCompare(b.fullName))
        .map((client) => ({
          id: client.id,
          label: `${client.fullName} - ${client.postcode || client.townCity || 'No postcode'}`,
        }));
    },
    firstCallItems() {
      return this.runForm.clientIds
        .map((id) => this.clients.find((client) => Number(client.id) === Number(id)))
        .filter(Boolean)
        .map((client) => ({
          id: client.id,
          label: `${client.fullName} - ${client.fullAddress || client.postcode || 'Address pending'}`,
        }));
    },
    carerItems() {
      return this.carers;
    },
    filteredClients() {
      const query = String(this.clientFilters.search || '').trim().toLowerCase();
      return this.clients.filter((client) => {
        if (this.clientFilters.status === 'active' && !client.isActive) return false;
        if (this.clientFilters.status === 'inactive' && client.isActive) return false;
        if (!query) return true;
        const haystack = [
          client.fullName,
          client.fullAddress,
          client.postcode,
          client.notes,
          client.areaZone,
        ].join(' ').toLowerCase();
        return haystack.includes(query);
      });
    },
    filteredRuns() {
      const query = String(this.runFilters.search || '').trim().toLowerCase();
      const dateFilter = String(this.runFilters.date || '').trim();
      return this.runs.filter((run) => {
        if (dateFilter && run.runDate !== dateFilter) return false;
        if (!query) return true;
        const haystack = [
          run.runName,
          run.runDate,
          run.shiftLabel,
          run.firstCallName,
          run.assignedCarerName,
        ].join(' ').toLowerCase();
        return haystack.includes(query);
      });
    },
    canSaveClient() {
      return Boolean(
        this.clientForm.fullName.trim() &&
        this.clientForm.addressLine1.trim() &&
        this.clientForm.townCity.trim() &&
        this.clientForm.postcode.trim()
      );
    },
    clientLookupAddress() {
      return [
        this.clientForm.addressLine1,
        this.clientForm.addressLine2,
        this.clientForm.townCity,
        this.clientForm.county,
        this.clientForm.postcode,
      ]
        .map((value) => String(value || '').trim())
        .filter(Boolean)
        .join(', ');
    },
    hasClientCoordinates() {
      return this.coordinateValue(this.clientForm.latitude) !== null && this.coordinateValue(this.clientForm.longitude) !== null;
    },
    hasClientLookupAddress() {
      return Boolean(
        this.clientForm.addressLine1.trim() &&
        this.clientForm.townCity.trim() &&
        this.clientForm.postcode.trim()
      );
    },
    canSearchClientAddress() {
      return this.mapLookup.enabled && this.hasClientLookupAddress;
    },
    canOpenClientMap() {
      return this.hasClientCoordinates || this.hasClientLookupAddress;
    },
    clientMapHasDraftCoordinates() {
      return this.coordinateValue(this.clientMapDraft.latitude) !== null && this.coordinateValue(this.clientMapDraft.longitude) !== null;
    },
    canUseGoogleClientMap() {
      return Boolean(!this.clientMapGoogleFailed && this.mapLookup.googleJsEnabled && String(this.mapLookup.browserApiKey || '').trim());
    },
    clientMapProviderLabel() {
      if (this.canUseGoogleClientMap) {
        return 'Google interactive map';
      }
      if (this.mapLookup.enabled) {
        return 'Address search with manual map';
      }
      return 'Manual pin placement map';
    },
    clientMapSupports3d() {
      return this.canUseGoogleClientMap;
    },
    clientMapExternalUrl() {
      const latitude = this.coordinateValue(this.clientMapDraft.latitude) ?? this.coordinateValue(this.clientForm.latitude);
      const longitude = this.coordinateValue(this.clientMapDraft.longitude) ?? this.coordinateValue(this.clientForm.longitude);

      if (latitude !== null && longitude !== null) {
        return `https://www.google.com/maps?q=${encodeURIComponent(`${latitude},${longitude}`)}`;
      }

      if (this.clientLookupAddress) {
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(this.clientLookupAddress)}`;
      }

      return '';
    },
    clientCoordinatesNeedReview() {
      return (
        this.hasClientCoordinates &&
        this.clientMapConfirmedSignature !== '' &&
        this.clientMapConfirmedSignature !== this.currentClientAddressSignature()
      );
    },
    canGenerateRoute() {
      return Boolean(
        this.runForm.runName.trim() &&
        this.runForm.runDate &&
        this.runForm.shiftLabel.trim() &&
        this.runForm.clientIds.length &&
        this.runForm.firstCallClientId
      );
    },
    isPreviewDirty() {
      return this.previewSignature !== '' && this.previewSignature !== this.currentPreviewSignature();
    },
    canSaveRun() {
      return Boolean(this.routePreview.stops.length) && !this.isPreviewDirty;
    },
  },
  async created() {
    await this.loadBootstrap();
  },
  watch: {
    clientMapDialog(value) {
      if (!value) {
        this.destroyClientMapPicker();
      }
    },
  },
  beforeUnmount() {
    if (this._clientMapRefreshTimer) {
      window.clearTimeout(this._clientMapRefreshTimer);
      this._clientMapRefreshTimer = null;
    }
    this.destroyClientMapPicker();
  },
  methods: {
    showMessage(text, type = 'error') {
      this.snackbar = {
        show: true,
        text: String(text || '').trim() || 'Request completed.',
        type: type === 'success' ? 'success' : type === 'info' ? 'info' : 'error',
      };
    },
    formatDate(value) {
      if (!value) return '-';
      const parsed = new Date(`${value}T00:00:00`);
      return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
      });
    },
    formatDateTime(value) {
      if (!value) return '-';
      const parsed = new Date(String(value).replace(' ', 'T'));
      return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      });
    },
    formatMethodLabel(value) {
      return String(value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase()) || 'Saved Route';
    },
    coordinateValue(value) {
      if (value === null || value === undefined || value === '') {
        return null;
      }

      const parsed = Number(value);
      return Number.isFinite(parsed) ? parsed : null;
    },
    formatCoordinate(value) {
      const parsed = this.coordinateValue(value);
      return parsed === null ? 'Pending' : parsed.toFixed(6);
    },
    formatLocationType(value) {
      return String(value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase()) || 'Address Match';
    },
    currentClientAddressSignature() {
      return [
        this.clientForm.addressLine1,
        this.clientForm.addressLine2,
        this.clientForm.townCity,
        this.clientForm.county,
        this.clientForm.postcode,
      ]
        .map((value) => String(value || '').trim().toLowerCase())
        .join('|');
    },
    syncClientMapSignatureFromForm() {
      this.clientMapConfirmedSignature = this.hasClientCoordinates ? this.currentClientAddressSignature() : '';
    },
    resetClientMapState() {
      this.clientMapDialog = false;
      this.clientMapLookupLoading = false;
      this.clientMapError = '';
      this.clientMapInfo = '';
      this.clientMapDraft = emptyClientMapDraft();
      this.clientMapCenter = {
        latitude: DEFAULT_CLIENT_MAP.latitude,
        longitude: DEFAULT_CLIENT_MAP.longitude,
      };
      this.clientMapZoom = DEFAULT_CLIENT_MAP.zoom;
      this.clientMapType = DEFAULT_CLIENT_MAP.type;
      this.clientMapViewMode = '2d';
      this.clientMapHeading = 0;
      this.clientMapGoogleFailed = false;
    },
    primeClientMapDraftFromForm() {
      const latitude = this.coordinateValue(this.clientForm.latitude);
      const longitude = this.coordinateValue(this.clientForm.longitude);

      this.clientMapDraft = {
        ...emptyClientMapDraft(),
        latitude,
        longitude,
      };
      this.clientMapCenter = {
        latitude: latitude ?? DEFAULT_CLIENT_MAP.latitude,
        longitude: longitude ?? DEFAULT_CLIENT_MAP.longitude,
      };
      this.clientMapZoom = latitude !== null && longitude !== null ? 17 : DEFAULT_CLIENT_MAP.zoom;
      this.clientMapViewMode = '2d';
      this.clientMapHeading = 0;
      this.clientMapGoogleFailed = false;
      this.clientMapError = '';
      this.clientMapInfo = latitude !== null && longitude !== null
        ? 'Drag the pin or click the map to refine the visit location.'
        : 'Search the address to drop a suggested pin, or click the map to place it manually.';
    },
    async openClientMap() {
      if (!this.canOpenClientMap) {
        this.showMessage('Enter address line 1, town/city, and postcode before opening the map.');
        return;
      }

      this.primeClientMapDraftFromForm();
      this.clientMapDialog = true;
      await this.$nextTick();
      this.queueClientMapRefresh(true);

      if (this.canSearchClientAddress) {
        await this.searchClientAddressOnMap();
      }
    },
    normalizeHeading(value) {
      const numeric = Number(value);
      if (!Number.isFinite(numeric)) {
        return 0;
      }

      return ((numeric % 360) + 360) % 360;
    },
    updateClientMapDraftCoordinates(latitude, longitude, message = 'Pin updated. Use this location if it looks correct.') {
      this.clientMapDraft = {
        ...this.clientMapDraft,
        latitude: Number(latitude.toFixed(7)),
        longitude: Number(longitude.toFixed(7)),
      };
      this.clientMapCenter = {
        latitude: Number(latitude),
        longitude: Number(longitude),
      };
      this.clientMapInfo = message;
    },
    applyLookupToClientMapDraft(lookup, message = 'Address found. Review the pin and confirm the visit location.') {
      const latitude = this.coordinateValue(lookup.latitude);
      const longitude = this.coordinateValue(lookup.longitude);

      if (latitude === null || longitude === null) {
        throw new Error('Address lookup returned unusable coordinates.');
      }

      this.clientMapDraft = {
        ...emptyClientMapDraft(),
        latitude,
        longitude,
        formattedAddress: lookup.formattedAddress || this.clientLookupAddress,
        placeId: lookup.placeId || '',
        locationType: lookup.locationType || '',
        partialMatch: Boolean(lookup.partialMatch),
      };
      this.clientMapCenter = { latitude, longitude };
      this.clientMapZoom = 17;
      this.clientMapInfo = message;
      this.queueClientMapRefresh(true);
    },
    async ensureGoogleClientMapsApi() {
      if (!this.canUseGoogleClientMap) {
        throw new Error('Google Maps browser search is not configured for this environment.');
      }

      const maps = await loadGoogleMapsApi({
        apiKey: this.mapLookup.browserApiKey,
        region: this.mapLookup.country || 'GB',
        language: 'en-GB',
      });

      if (typeof maps.importLibrary === 'function') {
        await Promise.all([
          maps.importLibrary('maps'),
          maps.importLibrary('marker'),
        ]);
      }

      return window.google.maps;
    },
    extractGoogleLatLng(latLng) {
      if (!latLng) {
        return null;
      }

      const latitude = typeof latLng.lat === 'function' ? latLng.lat() : latLng.lat;
      const longitude = typeof latLng.lng === 'function' ? latLng.lng() : latLng.lng;
      const safeLatitude = this.coordinateValue(latitude);
      const safeLongitude = this.coordinateValue(longitude);

      if (safeLatitude === null || safeLongitude === null) {
        return null;
      }

      return {
        latitude: safeLatitude,
        longitude: safeLongitude,
      };
    },
    async lookupClientAddressWithGoogle() {
      await this.ensureGoogleClientMapsApi();

      if (!this._clientGoogleGeocoder) {
        this._clientGoogleGeocoder = new window.google.maps.Geocoder();
      }

      const country = String(this.mapLookup.country || 'GB').trim();
      const response = await this._clientGoogleGeocoder.geocode({
        address: this.clientLookupAddress,
        componentRestrictions: { country },
        region: country.toLowerCase(),
      });

      const result = Array.isArray(response?.results) ? response.results[0] : null;
      if (!result) {
        throw new Error('No map match was found for that address. Check the postcode or refine the address and try again.');
      }

      const coordinates = this.extractGoogleLatLng(result.geometry?.location);
      if (!coordinates) {
        throw new Error('Google returned the address without usable coordinates.');
      }

      return {
        latitude: coordinates.latitude,
        longitude: coordinates.longitude,
        formattedAddress: result.formatted_address || this.clientLookupAddress,
        placeId: result.place_id || '',
        locationType: result.geometry?.location_type || '',
        partialMatch: Boolean(result.partial_match),
      };
    },
    async searchClientAddressOnMap(showSnackbar = false) {
      if (this.clientMapLookupLoading) {
        return;
      }

      if (!this.hasClientLookupAddress) {
        this.clientMapError = 'Enter address line 1, town/city, and postcode before searching the map.';
        return;
      }

      if (!this.mapLookup.enabled) {
        this.clientMapError = 'Address search is not configured yet. You can still place the pin manually.';
        return;
      }

      this.clientMapLookupLoading = true;
      this.clientMapError = '';

      try {
        let lookup = null;
        let successMessage = 'Address found. Review the pin and confirm the visit location.';
        let browserLookupError = null;

        if (this.canUseGoogleClientMap) {
          try {
            lookup = await this.lookupClientAddressWithGoogle();
          } catch (error) {
            browserLookupError = error;
          }
        }

        if (!lookup) {
          const payload = await lookupRouteClientAddress({
            addressLine1: this.clientForm.addressLine1,
            addressLine2: this.clientForm.addressLine2,
            townCity: this.clientForm.townCity,
            county: this.clientForm.county,
            postcode: this.clientForm.postcode,
          });
          lookup = payload.lookup || {};
          successMessage = payload.message || successMessage;
          if (browserLookupError && !this.canUseGoogleClientMap) {
            this.clientMapInfo = describeRouteOptimiserError(browserLookupError, successMessage);
          }
        }

        this.applyLookupToClientMapDraft(lookup, successMessage);

        if (showSnackbar) {
          this.showMessage('Address found on the map. Confirm the pin to save the coordinates.', 'success');
        }
      } catch (error) {
        const message = describeRouteOptimiserError(error, 'Unable to look up that address.');
        this.clientMapError = message;
        if (showSnackbar) {
          this.showMessage(message);
        }
      } finally {
        this.clientMapLookupLoading = false;
      }
    },
    applyClientMapCoordinates() {
      const latitude = this.coordinateValue(this.clientMapDraft.latitude);
      const longitude = this.coordinateValue(this.clientMapDraft.longitude);

      if (latitude === null || longitude === null) {
        this.clientMapError = 'Choose a point on the map first.';
        return;
      }

      this.clientForm.latitude = latitude.toFixed(7);
      this.clientForm.longitude = longitude.toFixed(7);
      this.clientMapConfirmedSignature = this.currentClientAddressSignature();
      this.clientMapDialog = false;
      this.showMessage('Client coordinates updated from the map.', 'success');
    },
    clearClientCoordinates() {
      this.clientForm.latitude = '';
      this.clientForm.longitude = '';
      this.clientMapConfirmedSignature = '';
      this.clientMapDraft = emptyClientMapDraft();
      this.clientMapError = '';
      this.clientMapInfo = '';
      this.showMessage('Client coordinates cleared.', 'info');
    },
    setClientMapViewMode(mode) {
      if (!this.clientMapSupports3d) {
        return;
      }

      this.clientMapViewMode = mode === '3d' ? '3d' : '2d';
      if (this.clientMapViewMode === '3d' && this.clientMapZoom < 18) {
        this.clientMapZoom = 18;
      }
      this.queueClientMapRefresh(false);
    },
    rotateClientMap(delta) {
      if (!this.clientMapSupports3d) {
        return;
      }

      if (this.clientMapViewMode !== '3d') {
        this.clientMapViewMode = '3d';
        if (this.clientMapZoom < 18) {
          this.clientMapZoom = 18;
        }
      }

      this.clientMapHeading = this.normalizeHeading(this.clientMapHeading + delta);
      this.queueClientMapRefresh(false);
    },
    recenterClientMap() {
      this.queueClientMapRefresh(true);
    },
    queueClientMapRefresh(recenter = false) {
      if (!this.clientMapDialog) {
        return;
      }

      this._clientMapNeedsRecenter = Boolean(this._clientMapNeedsRecenter || recenter);
      if (this._clientMapRefreshTimer) {
        return;
      }

      this._clientMapRefreshTimer = window.setTimeout(() => {
        const shouldRecenter = Boolean(this._clientMapNeedsRecenter);
        this._clientMapNeedsRecenter = false;
        this._clientMapRefreshTimer = null;
        void this.refreshClientMapPicker(shouldRecenter);
      }, 0);
    },
    updateClientMapType(value) {
      this.clientMapType = value === 'satellite' ? 'satellite' : 'roadmap';
      this.queueClientMapRefresh(false);
    },
    currentClientMapViewPosition() {
      return {
        latitude: this.coordinateValue(this.clientMapDraft.latitude) ?? this.clientMapCenter.latitude,
        longitude: this.coordinateValue(this.clientMapDraft.longitude) ?? this.clientMapCenter.longitude,
      };
    },
    applyGoogleClientMapCamera(instance, recenter = false) {
      if (!instance?.map) {
        return;
      }

      const position = this.currentClientMapViewPosition();
      const heading = this.clientMapViewMode === '3d' ? this.normalizeHeading(this.clientMapHeading) : 0;
      const tilt = this.clientMapViewMode === '3d' ? 45 : 0;

      instance.map.setMapTypeId(this.clientMapType === 'satellite' ? 'satellite' : 'roadmap');
      instance.map.setHeading(heading);
      instance.map.setTilt(tilt);

      if (instance.map.getZoom() !== this.clientMapZoom) {
        instance.map.setZoom(this.clientMapZoom);
      }

      if (recenter) {
        instance.map.panTo({ lat: position.latitude, lng: position.longitude });
      }
    },
    ensureGoogleClientMarker(instance) {
      if (!instance?.map) {
        return;
      }

      const latitude = this.coordinateValue(this.clientMapDraft.latitude);
      const longitude = this.coordinateValue(this.clientMapDraft.longitude);

      if (latitude === null || longitude === null) {
        if (instance.marker) {
          instance.marker.setMap(null);
          instance.marker = null;
        }
        return;
      }

      const position = { lat: latitude, lng: longitude };

      if (!instance.marker) {
        instance.marker = new window.google.maps.Marker({
          map: instance.map,
          position,
          draggable: true,
          title: 'Client visit location',
        });

        instance.marker.addListener('dragend', (event) => {
          const coordinates = this.extractGoogleLatLng(event.latLng);
          if (!coordinates) {
            return;
          }

          this.updateClientMapDraftCoordinates(coordinates.latitude, coordinates.longitude);
        });
        return;
      }

      instance.marker.setMap(instance.map);
      instance.marker.setPosition(position);
    },
    async createGoogleClientMapPicker(container) {
      const maps = await this.ensureGoogleClientMapsApi();
      const position = this.currentClientMapViewPosition();
      const options = {
        center: { lat: position.latitude, lng: position.longitude },
        zoom: this.clientMapZoom,
        mapTypeId: this.clientMapType === 'satellite' ? 'satellite' : 'roadmap',
        streetViewControl: true,
        fullscreenControl: true,
        scaleControl: true,
        rotateControl: true,
        mapTypeControl: false,
        zoomControl: true,
        gestureHandling: 'greedy',
      };

      if (maps.RenderingType) {
        options.renderingType = maps.RenderingType.VECTOR;
      }

      const map = new maps.Map(container, options);
      const instance = {
        engine: 'google',
        map,
        marker: null,
      };

      map.addListener('click', (event) => {
        const coordinates = this.extractGoogleLatLng(event.latLng);
        if (!coordinates) {
          return;
        }

        this.updateClientMapDraftCoordinates(coordinates.latitude, coordinates.longitude);
        this.ensureGoogleClientMarker(instance);
      });

      map.addListener('zoom_changed', () => {
        const zoom = map.getZoom();
        if (Number.isFinite(zoom)) {
          this.clientMapZoom = Number(zoom);
        }
      });

      map.addListener('heading_changed', () => {
        const heading = map.getHeading();
        if (Number.isFinite(heading)) {
          this.clientMapHeading = this.normalizeHeading(heading);
        }
      });

      map.addListener('tilt_changed', () => {
        const tilt = map.getTilt();
        if (Number.isFinite(tilt) && Number(tilt) <= 0 && this.clientMapViewMode === '3d') {
          this.clientMapViewMode = '2d';
        }
      });

      this.ensureGoogleClientMarker(instance);
      this.applyGoogleClientMapCamera(instance, true);
      return instance;
    },
    async refreshGoogleClientMapPicker(recenter = false) {
      const container = this.$refs.clientMapCanvas;
      if (!container) {
        return;
      }

      let instance = this._clientMapPicker;
      if (!instance || instance.engine !== 'google') {
        this.destroyClientMapPicker();
        instance = await this.createGoogleClientMapPicker(container);
        this._clientMapPicker = instance;
      } else {
        this.ensureGoogleClientMarker(instance);
        this.applyGoogleClientMapCamera(instance, recenter);
      }
    },
    ensureClientMapMarker(instance) {
      if (!instance || !instance.L) {
        return;
      }

      const latitude = this.coordinateValue(this.clientMapDraft.latitude);
      const longitude = this.coordinateValue(this.clientMapDraft.longitude);

      if (latitude === null || longitude === null) {
        if (instance.marker) {
          instance.map.removeLayer(instance.marker);
          instance.marker = null;
        }
        return;
      }

      if (!instance.marker) {
        instance.marker = instance.L.marker([latitude, longitude], { draggable: true }).addTo(instance.map);
        instance.marker.on('dragend', () => {
          const position = instance.marker.getLatLng();
          this.clientMapDraft = {
            ...this.clientMapDraft,
            latitude: Number(position.lat.toFixed(7)),
            longitude: Number(position.lng.toFixed(7)),
          };
          this.clientMapCenter = {
            latitude: Number(position.lat),
            longitude: Number(position.lng),
          };
          this.clientMapInfo = 'Pin updated. Use this location if it looks correct.';
        });
        return;
      }

      instance.marker.setLatLng([latitude, longitude]);
    },
    createClientMapPicker(L, container) {
      const position = this.currentClientMapViewPosition();
      const map = L.map(container, {
        center: [position.latitude, position.longitude],
        zoom: this.clientMapZoom,
        scrollWheelZoom: true,
      });

      const instance = {
        L,
        map,
        marker: null,
        tileLayer: createLeafletTileLayer(L, this.clientMapType).addTo(map),
        mapType: this.clientMapType,
      };

      map.on('click', (event) => {
        const latitude = Number(event.latlng.lat.toFixed(7));
        const longitude = Number(event.latlng.lng.toFixed(7));
        this.clientMapDraft = {
          ...this.clientMapDraft,
          latitude,
          longitude,
        };
        this.clientMapCenter = { latitude, longitude };
        this.ensureClientMapMarker(instance);
        this.clientMapInfo = 'Pin updated. Use this location if it looks correct.';
      });

      map.on('zoomend', () => {
        this.clientMapZoom = map.getZoom();
      });

      this.ensureClientMapMarker(instance);
      return instance;
    },
    syncClientMapPicker(L, recenter = false) {
      const container = this.$refs.clientMapCanvas;
      if (!container) {
        return;
      }

      let instance = this._clientMapPicker;
      if (!instance) {
        instance = this.createClientMapPicker(L, container);
        this._clientMapPicker = instance;
      } else {
        if (instance.mapType !== this.clientMapType) {
          instance.map.removeLayer(instance.tileLayer);
          instance.tileLayer = createLeafletTileLayer(L, this.clientMapType).addTo(instance.map);
          instance.mapType = this.clientMapType;
        }

        this.ensureClientMapMarker(instance);
      }

      const position = this.currentClientMapViewPosition();
      if (recenter) {
        instance.map.setView([position.latitude, position.longitude], this.clientMapZoom, { animate: false });
      }

      window.requestAnimationFrame(() => {
        instance.map.invalidateSize();
      });
    },
    async refreshClientMapPicker(recenter = false) {
      if (!this.clientMapDialog) {
        return;
      }

      try {
        if (this.canUseGoogleClientMap) {
          await this.refreshGoogleClientMapPicker(recenter);
          return;
        }

        const L = await loadLeafletAssets();
        this.syncClientMapPicker(L, recenter);
      } catch (error) {
        const message = error?.message || 'Unable to load the map picker.';

        if (this.canUseGoogleClientMap) {
          this.clientMapGoogleFailed = true;
          this.destroyClientMapPicker();
          this.clientMapError = `${message} Switched to the manual pin map instead.`;

          try {
            const L = await loadLeafletAssets();
            this.syncClientMapPicker(L, recenter);
            return;
          } catch (leafletError) {
            this.clientMapError = leafletError?.message || this.clientMapError;
            return;
          }
        }

        this.clientMapError = message;
      }
    },
    destroyClientMapPicker() {
      const instance = this._clientMapPicker;
      if (!instance) {
        return;
      }

      if (instance.engine === 'google') {
        if (instance.marker) {
          instance.marker.setMap(null);
        }
        const container = this.$refs.clientMapCanvas;
        if (container) {
          container.innerHTML = '';
        }
      } else {
        instance.map.remove();
      }

      this._clientMapPicker = null;
    },
    currentPreviewSignature() {
      return `${[...this.runForm.clientIds].map(Number).sort((a, b) => a - b).join(',')}|${Number(this.runForm.firstCallClientId || 0)}`;
    },
    syncStats() {
      this.stats = {
        totalClients: this.clients.length,
        activeClients: this.clients.filter((client) => client.isActive).length,
        inactiveClients: this.clients.filter((client) => !client.isActive).length,
        savedRuns: this.runs.length,
        manualOverrideRuns: this.runs.filter((run) => run.manualOverride).length,
        upcomingRuns: this.runs.filter((run) => String(run.runDate || '') >= todayIso()).length,
      };
    },
    ensureFirstCall() {
      const validClientIds = this.runForm.clientIds.filter((id) =>
        this.clients.some((client) => Number(client.id) === Number(id))
      );
      this.runForm.clientIds = validClientIds;
      if (!validClientIds.includes(this.runForm.firstCallClientId)) {
        this.runForm.firstCallClientId = validClientIds[0] || null;
      }
    },
    async loadBootstrap() {
      this.loading = true;
      try {
        const payload = await fetchRouteOptimiserBootstrap();
        this.clients = Array.isArray(payload.clients) ? payload.clients : [];
        this.runs = Array.isArray(payload.runs) ? payload.runs : [];
        this.carers = Array.isArray(payload.carers) ? payload.carers : [];
        this.stats = { ...DEFAULT_STATS, ...(payload.stats || {}) };
        this.mapLookup = { ...emptyMapLookup(), ...(payload.mapLookup || {}) };
        this.ensureFirstCall();
      } catch (error) {
        this.showMessage(describeRouteOptimiserError(error, 'Failed to load route optimiser.'));
      } finally {
        this.loading = false;
      }
    },
    handleClientSelectionChange(values) {
      this.runForm.clientIds = [...new Set((values || []).map((value) => Number(value)).filter((value) => value > 0))];
      this.ensureFirstCall();
      if (!this.runForm.clientIds.length) {
        this.routePreview = { stops: [], optimisationMethod: '', summary: null };
        this.previewSignature = '';
      }
    },
    resetClientForm() {
      this.clientForm = emptyClientForm();
      this.resetClientMapState();
      this.syncClientMapSignatureFromForm();
    },
    upsertClient(client) {
      const next = [...this.clients];
      const index = next.findIndex((item) => Number(item.id) === Number(client.id));
      if (index >= 0) {
        next.splice(index, 1, client);
      } else {
        next.push(client);
      }
      next.sort((a, b) => a.fullName.localeCompare(b.fullName));
      this.clients = next;
      this.ensureFirstCall();
    },
    replaceClientInPreview(client) {
      if (!client || !this.routePreview.stops.length) {
        return;
      }
      this.routePreview.stops = this.routePreview.stops.map((stop) =>
        Number(stop.clientId) === Number(client.id)
          ? { ...stop, ...client, clientId: client.id, id: client.id }
          : stop
      );
    },
    async submitClient() {
      if (!this.canSaveClient || this.savingClient) {
        return;
      }

      this.savingClient = true;
      try {
        const payload = await saveRouteClient({
          ...this.clientForm,
          latitude: this.clientForm.latitude === '' ? null : Number(this.clientForm.latitude),
          longitude: this.clientForm.longitude === '' ? null : Number(this.clientForm.longitude),
        });
        this.upsertClient(payload.client);
        this.syncStats();
        this.replaceClientInPreview(payload.client);
        this.showMessage(payload.message || 'Client saved successfully.', 'success');
        this.resetClientForm();
      } catch (error) {
        this.showMessage(describeRouteOptimiserError(error, 'Failed to save client.'));
      } finally {
        this.savingClient = false;
      }
    },
    openClientDetail(client) {
      this.selectedClient = client;
      this.clientDetailDialog = true;
    },
    editClient(client) {
      this.clientForm = {
        id: client.id,
        fullName: client.fullName || '',
        addressLine1: client.addressLine1 || '',
        addressLine2: client.addressLine2 || '',
        townCity: client.townCity || '',
        county: client.county || '',
        postcode: client.postcode || '',
        notes: client.notes || '',
        preferredCallType: client.preferredCallType || '',
        areaZone: client.areaZone || '',
        latitude: client.latitude ?? '',
        longitude: client.longitude ?? '',
        isActive: Boolean(client.isActive),
      };
      this.resetClientMapState();
      this.syncClientMapSignatureFromForm();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    promptDeleteClient(client) {
      this.deleteClientTarget = client;
      this.deleteClientDialog = true;
    },
    async deleteClientConfirmed() {
      if (!this.deleteClientTarget || this.deletingClient) {
        return;
      }

      this.deletingClient = true;
      try {
        const payload = await deleteRouteClient(this.deleteClientTarget.id);
        this.clients = this.clients.filter((client) => Number(client.id) !== Number(payload.deletedId));
        this.runForm.clientIds = this.runForm.clientIds.filter((id) => Number(id) !== Number(payload.deletedId));
        this.ensureFirstCall();
        this.routePreview = { stops: [], optimisationMethod: '', summary: null };
        this.previewSignature = '';
        this.syncStats();
        this.deleteClientDialog = false;
        this.deleteClientTarget = null;
        this.showMessage(payload.message || 'Client deleted successfully.', 'success');
      } catch (error) {
        this.showMessage(describeRouteOptimiserError(error, 'Failed to delete client.'));
      } finally {
        this.deletingClient = false;
      }
    },
    resetRunForm() {
      this.runForm = emptyRunForm();
      this.routePreview = { stops: [], optimisationMethod: '', summary: null };
      this.previewSignature = '';
    },
    async generateRoute() {
      if (!this.canGenerateRoute || this.generatingRoute) {
        return;
      }

      this.generatingRoute = true;
      try {
        const payload = await generateOptimisedRoute({
          clientIds: this.runForm.clientIds,
          firstCallClientId: this.runForm.firstCallClientId,
        });
        this.routePreview = {
          stops: Array.isArray(payload.stops) ? payload.stops : [],
          optimisationMethod: payload.optimisationMethod || '',
          summary: payload.summary || null,
        };
        this.previewSignature = this.currentPreviewSignature();
        this.showMessage(payload.message || 'Route generated successfully.', 'success');
      } catch (error) {
        this.showMessage(describeRouteOptimiserError(error, 'Failed to generate route.'));
      } finally {
        this.generatingRoute = false;
      }
    },
    canMoveStop(index, direction) {
      const nextIndex = index + direction;
      if (index <= 0) return false;
      if (nextIndex <= 0) return false;
      return nextIndex >= 0 && nextIndex < this.routePreview.stops.length;
    },
    moveStop(index, direction) {
      if (!this.canMoveStop(index, direction)) {
        return;
      }

      const nextIndex = index + direction;
      const nextStops = [...this.routePreview.stops];
      const temp = nextStops[index];
      nextStops[index] = nextStops[nextIndex];
      nextStops[nextIndex] = temp;

      this.routePreview.stops = nextStops.map((stop, stopIndex) => ({
        ...stop,
        routeOrder: stopIndex + 1,
        isFirstCall: stopIndex === 0,
        manualOverride: stopIndex === 0 ? false : true,
        segmentLabel: stopIndex === 0 ? 'Fixed first call' : 'Manual order adjustment',
        segmentMethod: stopIndex === 0 ? 'fixed_first_call' : 'manual',
        segmentDistanceKm: null,
        segmentScore: null,
      }));
    },
    upsertRun(run) {
      const next = [...this.runs];
      const index = next.findIndex((item) => Number(item.id) === Number(run.id));
      if (index >= 0) {
        next.splice(index, 1, run);
      } else {
        next.unshift(run);
      }
      next.sort((a, b) => String(b.runDate || '').localeCompare(String(a.runDate || '')) || Number(b.id) - Number(a.id));
      this.runs = next;
    },
    async saveRun() {
      if (!this.canSaveRun || this.savingRun) {
        return;
      }

      this.savingRun = true;
      try {
        const assignedCarer = this.carers.find((item) => Number(item.id) === Number(this.runForm.assignedCarerAccountId || 0));
        const payload = await saveRouteRun({
          ...this.runForm,
          assignedCarerName: assignedCarer?.label || '',
          optimisationMethod: this.routePreview.optimisationMethod,
          manualOverride: this.routePreview.stops.some((stop, index) => index > 0 && stop.manualOverride),
          stops: this.routePreview.stops.map((stop) => ({
            clientId: stop.clientId,
            routeOrder: stop.routeOrder,
            isFirstCall: stop.isFirstCall,
            manualOverride: stop.manualOverride,
            segmentMethod: stop.segmentMethod,
            segmentLabel: stop.segmentLabel,
            segmentDistanceKm: stop.segmentDistanceKm,
            segmentScore: stop.segmentScore,
          })),
        });
        this.upsertRun(payload.run);
        this.routePreview.stops = Array.isArray(payload.stops) ? payload.stops : [];
        this.routePreview.optimisationMethod = payload.run?.optimisationMethod || this.routePreview.optimisationMethod;
        this.previewSignature = this.currentPreviewSignature();
        this.runForm.id = payload.run.id;
        this.syncStats();
        this.showMessage(payload.message || 'Run saved successfully.', 'success');
      } catch (error) {
        this.showMessage(describeRouteOptimiserError(error, 'Failed to save run.'));
      } finally {
        this.savingRun = false;
      }
    },
    async editRun(run) {
      if (!run || this.loadingRunId) {
        return;
      }

      this.loadingRunId = run.id;
      try {
        const payload = await fetchRouteRun(run.id);
        const detailRun = payload.run || {};
        const stops = Array.isArray(payload.stops) ? payload.stops : [];
        this.runForm = {
          id: detailRun.id || 0,
          runName: detailRun.runName || '',
          runDate: detailRun.runDate || todayIso(),
          shiftLabel: detailRun.shiftLabel || 'Morning Run',
          assignedCarerAccountId: detailRun.assignedCarerAccountId || null,
          notes: detailRun.notes || '',
          clientIds: stops.map((stop) => stop.clientId),
          firstCallClientId: detailRun.firstCallClientId || stops[0]?.clientId || null,
        };
        this.routePreview = {
          stops,
          optimisationMethod: detailRun.optimisationMethod || '',
          summary: null,
        };
        this.previewSignature = this.currentPreviewSignature();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } catch (error) {
        this.showMessage(describeRouteOptimiserError(error, 'Failed to load saved run.'));
      } finally {
        this.loadingRunId = 0;
      }
    },
    promptDeleteRun(run) {
      this.deleteRunTarget = run;
      this.deleteRunDialog = true;
    },
    async deleteRunConfirmed() {
      if (!this.deleteRunTarget || this.deletingRun) {
        return;
      }

      this.deletingRun = true;
      try {
        const payload = await deleteRouteRun(this.deleteRunTarget.id);
        this.runs = this.runs.filter((run) => Number(run.id) !== Number(payload.deletedId));
        if (Number(this.runForm.id) === Number(payload.deletedId)) {
          this.resetRunForm();
        }
        this.syncStats();
        this.deleteRunDialog = false;
        this.deleteRunTarget = null;
        this.showMessage(payload.message || 'Run deleted successfully.', 'success');
      } catch (error) {
        this.showMessage(describeRouteOptimiserError(error, 'Failed to delete run.'));
      } finally {
        this.deletingRun = false;
      }
    },
  },
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap');

.route-page {
  --bg-shell: #f4f4f6;
  --bg-card: #ffffff;
  --text-main: #1f1f2a;
  --text-muted: #656474;
  --line: #e6e5eb;

  min-height: calc(100vh - 64px);
  padding: 20px;
  background:
    radial-gradient(820px 320px at 100% 105%, rgba(22, 124, 196, 0.12), transparent 64%),
    radial-gradient(720px 360px at -10% -5%, rgba(171, 32, 125, 0.12), transparent 62%),
    var(--bg-shell);
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
}

.route-shell {
  max-width: 1360px;
  margin: 0 auto;
}

.shell-header,
.panel-title-split,
.route-toolbar,
.header-actions,
.route-meta,
.route-toolbar-actions {
  display: flex;
  align-items: center;
}

.shell-header,
.panel-title-split,
.route-toolbar {
  justify-content: space-between;
}

.shell-header {
  gap: 16px;
  align-items: flex-start;
  margin-bottom: 14px;
}

.header-kicker {
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-size: 0.74rem;
  font-weight: 700;
  color: var(--text-muted);
}

.shell-header h1 {
  margin: 2px 0 4px;
  font-family: 'Space Grotesk', 'Plus Jakarta Sans', sans-serif;
  color: var(--text-main);
  font-size: clamp(1.5rem, 2.3vw, 2rem);
  line-height: 1.08;
}

.header-copy {
  margin: 0;
  color: var(--text-muted);
  max-width: 780px;
}

.header-actions,
.route-meta,
.route-toolbar-actions {
  gap: 10px;
}

.stats-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-bottom: 14px;
}

.stat-card,
.panel-card {
  border: 1px solid var(--line);
  border-radius: 16px;
  background: var(--bg-card);
}

.stat-card {
  padding: 14px 16px;
}

.stat-card p {
  margin: 0;
  font-size: 0.78rem;
  color: var(--text-muted);
}

.stat-card h3 {
  margin: 8px 0 0;
  font-size: 1.5rem;
  color: var(--text-main);
}

.editor-grid {
  display: grid;
  gap: 14px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin-bottom: 14px;
}

.panel-card {
  margin-bottom: 14px;
}

.panel-title {
  font-family: 'Space Grotesk', 'Plus Jakarta Sans', sans-serif;
  color: var(--text-main);
}

.filters-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 220px;
  gap: 10px;
  margin-bottom: 12px;
}

.table-shell {
  border: 1px solid var(--line);
  border-radius: 12px;
  overflow: auto;
}

.empty-row {
  text-align: center;
  color: var(--text-muted);
  padding: 16px 8px;
}

.run-actions {
  gap: 10px;
}

.map-action-stack {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-top: 4px;
}

.map-action-row,
.map-chip-row,
.map-dialog-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}

.map-chip-row {
  gap: 8px;
}

.map-dialog-card {
  border-radius: 18px;
  overflow: hidden;
}

.map-dialog-copy {
  margin-bottom: 12px;
  color: var(--text-muted);
}

.map-dialog-toolbar {
  margin-bottom: 12px;
}

.map-dialog-address {
  margin-bottom: 12px;
  padding: 10px 12px;
  border: 1px dashed var(--line);
  border-radius: 12px;
  background: rgba(31, 31, 42, 0.02);
  color: var(--text-muted);
}

.map-chip-row-tight {
  margin: 0 0 12px;
}

.route-client-map {
  min-height: 420px;
  height: 420px;
  border: 1px solid var(--line);
  border-radius: 14px;
  overflow: hidden;
  background: #eef1f6;
}

.route-client-map :deep(.gm-style) {
  width: 100%;
  height: 100%;
}

.route-client-map :deep(.leaflet-container) {
  width: 100%;
  min-height: 420px;
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
}

.route-toolbar {
  gap: 12px;
  margin-bottom: 12px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.detail-grid strong {
  display: block;
  color: var(--text-main);
  margin-bottom: 4px;
}

.detail-grid p {
  margin: 0;
  color: var(--text-muted);
}

.detail-span {
  grid-column: 1 / -1;
}

@media (max-width: 1100px) {
  .stats-grid,
  .editor-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .route-page {
    padding: 12px;
  }

  .shell-header,
  .header-actions,
  .panel-title-split,
  .route-toolbar,
  .map-dialog-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .stats-grid,
  .editor-grid,
  .filters-row,
  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
