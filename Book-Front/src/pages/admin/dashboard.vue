<template>
    <v-card title="Dashboard" solo border rounded="lg">
        <v-card-text>
            <v-row align="center" justify="center" dense>
                <v-col cols="12" md="6">
                    <v-card append-icon="mdi-check" variant="outlined" color="success" class="mx-auto pa-5" prepend-icon="mdi-account"
                        title="จำนวนผู้ใช้งาน">
                    </v-card>
                </v-col>

                <v-col cols="12" md="6">
                    <v-card append-icon="mdi-check" variant="outlined" color="primary" class="mx-auto pa-5" prepend-icon="mdi-cash-multiple"
                        title="ยอดขายทั้งหมด">
                    </v-card>
                </v-col>

                <v-col cols="12" md="6">
                    <v-card append-icon="mdi-check" color="success" class="mx-auto pa-5" prepend-icon="mdi-account"
                        title="จำนวนผู้ใช้งาน">
                    </v-card>
                </v-col>
                <v-col cols="12" md="6">
                    <v-card append-icon="mdi-check" color="success" class="mx-auto pa-5" prepend-icon="mdi-account"
                        title="จำนวนผู้ใช้งาน">
                    </v-card>
                </v-col>
            </v-row>
        </v-card-text>
    </v-card>
</template>
<script setup>
</script>