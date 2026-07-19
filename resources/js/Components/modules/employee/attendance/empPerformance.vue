<script setup>
import { computed } from 'vue'

const props = defineProps({
    performance: {
        type: Object,
        required: true,
        default: () => ({ month_label: '', rate: 0, working_days: 0, present: 0, late: 0, absent: 0 })
    },
    officeHours: {
        type: Object,
        required: true
    },
    holidays: {
        type: Array,
        default: () => []
    },
    // Resolved once at the page level and shared with the tracker (avoids a duplicate lookup)
    clientIp: {
        type: String,
        default: null
    }
})

// Responsive spans (the Performance column is full-width on phones, half-width on
// wide desktops). xs stacks so labels fit; rate/working 2-per-row from sm; and
// present/late/absent go 3-per-row only at lg, where the half column is wide enough.
const metrics = computed(() => [
    { key: 'rate',    label: 'Attendance rate', value: `${props.performance.rate ?? 0}%`,      icon: 'mdi-target',                 tone: 'primary', cols: 12, sm: 6 },
    { key: 'working', label: 'Working days',    value: props.performance.working_days ?? 0,    icon: 'mdi-calendar-month-outline', tone: 'info',    cols: 12, sm: 6 },
    { key: 'present', label: 'Present',         value: props.performance.present ?? 0,         icon: 'mdi-check',                  tone: 'success', cols: 6,  lg: 4 },
    { key: 'late',    label: 'Late arrivals',   value: props.performance.late ?? 0,            icon: 'mdi-clock-alert-outline',    tone: 'warn',    cols: 6,  lg: 4 },
    { key: 'absent',  label: 'Absent',          value: props.performance.absent ?? 0,          icon: 'mdi-close',                  tone: 'error',   cols: 6,  lg: 4 },
])
</script>

<template>
    <v-row dense class="fill-height align-content-start">
        <!-- Metrics -->
        <v-col v-for="m in metrics" :key="m.key" :cols="m.cols" :sm="m.sm" :lg="m.lg">
            <div class="card pstat" style="height:100%">
                <div class="pstat-body">
                    <div class="lbl">{{ m.label }}</div>
                    <div class="num">{{ m.value }}</div>
                </div>
                <span class="mic" :class="m.tone"><v-icon>{{ m.icon }}</v-icon></span>
            </div>
        </v-col>

        <!-- Office hours + IP (one row on sm+, stacked on xs) -->
        <v-col cols="12" sm="6">
            <div class="card pstat pstat--text" style="height:100%">
                <div class="pstat-body">
                    <div class="lbl">Office hours</div>
                    <div class="num">{{ officeHours.start }} – {{ officeHours.end }}</div>
                </div>
                <span class="mic info"><v-icon>mdi-clock-outline</v-icon></span>
            </div>
        </v-col>
        <v-col cols="12" sm="6">
            <div class="card pstat pstat--text" style="height:100%">
                <div class="pstat-body">
                    <div class="lbl">IP address</div>
                    <div class="num">{{ clientIp || '—' }}</div>
                </div>
                <span class="mic primary"><v-icon>mdi-ip-network-outline</v-icon></span>
            </div>
        </v-col>

        <!-- Holidays (full width) -->
        <v-col cols="12">
            <div class="card hcard">
                <div class="hhead">
                    <span class="mic holiday"><v-icon>mdi-calendar-star</v-icon></span>
                    <span class="htitle">Holidays</span>
                    <span class="hmonth">{{ performance.month_label }}</span>
                </div>
                <div class="hlist">
                    <div v-for="(h, i) in holidays" :key="i" class="hrow">
                        <span class="hdot"></span>
                        <span class="hname">{{ h.name }}</span>
                        <span class="hdate">{{ h.date }}</span>
                    </div>
                    <div v-if="!holidays.length" class="hempty">No holidays this month</div>
                </div>
            </div>
        </v-col>
    </v-row>
</template>
