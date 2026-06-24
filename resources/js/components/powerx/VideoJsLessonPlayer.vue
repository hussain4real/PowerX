<script setup lang="ts">
import videojs from 'video.js';
import type Player from 'video.js/dist/types/player';
import 'video.js/dist/video-js.css';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import type { LessonMedia } from '@/types';

type PlaybackProgress = {
    currentTime: number;
    duration: number;
};

const props = defineProps<{
    media: LessonMedia;
}>();

const emit = defineEmits<{
    started: [media: LessonMedia];
    progress: [media: LessonMedia, playback: PlaybackProgress];
    completed: [media: LessonMedia, playback: PlaybackProgress];
}>();

const videoElement = ref<HTMLVideoElement | null>(null);
const player = ref<Player | null>(null);

const source = computed(() => ({
    src: props.media.inlineUrl,
    type: props.media.mimeType || 'video/mp4',
}));

const readPlaybackProgress = (): PlaybackProgress | null => {
    const activePlayer = player.value;

    if (!activePlayer) {
        return null;
    }

    const currentTime = activePlayer.currentTime();
    const duration = activePlayer.duration();

    if (typeof currentTime !== 'number' || typeof duration !== 'number') {
        return null;
    }

    return {
        currentTime,
        duration,
    };
};

const handleStarted = (): void => {
    emit('started', props.media);
};

const handleProgress = (): void => {
    const playback = readPlaybackProgress();

    if (!playback) {
        return;
    }

    emit('progress', props.media, playback);
};

const handleCompleted = (): void => {
    const playback = readPlaybackProgress();

    emit('completed', props.media, playback ?? { currentTime: 0, duration: 0 });
};

const initializePlayer = async (): Promise<void> => {
    await nextTick();

    if (!videoElement.value || player.value) {
        return;
    }

    const activePlayer = videojs(videoElement.value, {
        controls: true,
        fill: true,
        liveui: true,
        playbackRates: [0.75, 1, 1.25, 1.5, 2],
        playsinline: true,
        preload: 'metadata',
        responsive: true,
        sources: [source.value],
    });

    activePlayer.on('play', handleStarted);
    activePlayer.on('timeupdate', handleProgress);
    activePlayer.on('ended', handleCompleted);

    player.value = activePlayer;
};

watch(
    source,
    (nextSource, previousSource) => {
        const activePlayer = player.value;

        if (
            !activePlayer ||
            (nextSource.src === previousSource.src &&
                nextSource.type === previousSource.type)
        ) {
            return;
        }

        activePlayer.src(nextSource);
        activePlayer.load();
    },
    { flush: 'post' },
);

onMounted(() => {
    void initializePlayer();
});

onBeforeUnmount(() => {
    if (!player.value) {
        return;
    }

    player.value.dispose();
    player.value = null;
});
</script>

<template>
    <div
        class="video-player-shell aspect-video overflow-hidden rounded-2xl border border-border bg-black"
        data-testid="videojs-lesson-player"
    >
        <video
            ref="videoElement"
            class="video-js vjs-big-play-centered vjs-theme-powerx h-full w-full"
            :aria-label="media.fileName"
            playsinline
            preload="metadata"
        />
    </div>
</template>

<style scoped>
.video-player-shell :deep(.video-js) {
    width: 100%;
    height: 100%;
    background: #000;
    color: #fff;
    font-family: inherit;
}

.video-player-shell :deep(.vjs-poster img) {
    object-fit: cover;
}

.video-player-shell :deep(.vjs-big-play-button) {
    width: 4rem;
    height: 4rem;
    border: 0;
    border-radius: 9999px;
    background: var(--powerx-yellow);
    color: var(--powerx-navy);
    line-height: 4rem;
    transition:
        background-color 150ms ease,
        transform 150ms ease;
}

.video-player-shell :deep(.vjs-big-play-button:hover),
.video-player-shell :deep(.vjs-big-play-button:focus) {
    background: var(--powerx-gold);
    transform: scale(1.04);
}

.video-player-shell :deep(.vjs-control-bar) {
    height: 3.25rem;
    background: rgba(2, 1, 1, 0.88);
}

.video-player-shell :deep(.vjs-play-progress),
.video-player-shell :deep(.vjs-volume-level) {
    background-color: var(--powerx-yellow);
}

.video-player-shell :deep(.vjs-load-progress div) {
    background: rgba(255, 255, 255, 0.28);
}

.video-player-shell :deep(.vjs-slider) {
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.18);
}

.video-player-shell :deep(.vjs-menu-content) {
    background: rgba(2, 1, 1, 0.94);
}
</style>
