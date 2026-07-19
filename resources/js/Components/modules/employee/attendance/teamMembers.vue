<script setup>
const props = defineProps({
    team: {
        type: Object,
        required: true,
        default: () => ({ present: 0, late: 0, absent: 0, members: [] })
    }
})

// Today's day + date for the header (e.g. "Sunday, 19 Jul")
const todayLabel = new Date().toLocaleDateString('en-GB', {
    weekday: 'long',
    day: '2-digit',
    month: 'short'
})

// Map a status key to a pill style class
const pillClass = (status) => {
    switch (status) {
        case 'working':
        case 'present':        return 'present'
        case 'late':           return 'late'
        case 'work_from_home': return 'wfh'
        case 'absent':         return 'absent'
        case 'leave':          return 'leave'
        case 'holiday':        return 'holiday'
        case 'weekend':        return 'weekend'
        default:               return 'absent'
    }
}
</script>

<template>
    <section class="card panel">
        <div class="sect-h">
            <h2>Team Members</h2>
            <span class="sub">{{ todayLabel }} · {{ team.members.length }}</span>
        </div>

        <div class="teamsum">
            <v-chip size="x-small" color="success" variant="tonal">{{ team.present }} Present</v-chip>
            <v-chip size="x-small" color="deeporange" variant="tonal">{{ team.late }} Late</v-chip>
            <v-chip size="x-small" color="error" variant="tonal">{{ team.absent }} Absent</v-chip>
        </div>

        <div class="tbl team">
            <div class="thead">
                <div class="th">Member</div>
                <div class="th">In</div>
                <div class="th r">Status</div>
            </div>

            <div v-for="member in team.members" :key="member.id" class="trow">
                <div class="cell-name">
                    <div class="td name">{{ member.name }}</div>
                    <div class="td role">{{ member.role }}</div>
                </div>
                <div class="td muted">{{ member.check_in || '—' }}</div>
                <div class="td r">
                    <span class="pill" :class="pillClass(member.status)">{{ member.status_label }}</span>
                </div>
            </div>

            <div v-if="!team.members.length" class="empty">No team members found</div>
        </div>
    </section>
</template>
