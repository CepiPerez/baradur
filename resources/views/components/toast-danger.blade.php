
<div id="toast" class="absolute top-0 mt-3 right-2.5 md:right-5" style="z-index:10;" x-show="show" x-data="{show:true}">

    <div class="flex items-center w-lg p-4 mb-4 text-red-700 rounded shadow  
        bg-red-100 border border-red-400" role="alert">
        <div class="inline-flex items-center rounded justify-center flex-shrink-0 w-8 h-8">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0zM232 152C232 138.8 242.8 128 256 128s24 10.75 24 24v128c0 13.25-10.75 24-24 24S232 293.3 232 280V152zM256 400c-17.36 0-31.44-14.08-31.44-31.44c0-17.36 14.07-31.44 31.44-31.44s31.44 14.08 31.44 31.44C287.4 385.9 273.4 400 256 400z"/></svg>
        </div>
        <div class="ml-2 mr-6 text-sm font-normal">{{$slot}}</div>
        <button type="button" class="ml-3 mr-1 -mx-2 -my-1.5 rounded p-1.5 inline-flex h-8 w-8 focus:ring-2 
            bg-red-200 hover:bg-red-300 text-red-700 focus:ring-red-300" 
            x-on:click="show=false" data-dismiss-target="#toast" aria-label="Close">
            <!-- <span class="sr-only">Close</span> -->
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
        </button>
    </div>
    
</div>