<!-- Left Sidenav -->
<div class="left-sidenav">
    <!-- LOGO -->
    <div class="brand mt-3">
        <a href="/akreditasi" class="logo">
            <span>
                <!-- Light-theme logo (for dark background) -->
                <img src="{{ asset('img/logo-belovacorp-bw.png')}}" alt="logo" class="logo-light" style="width: auto; height: 50px;">

                <!-- Dark-theme logo (for light background) -->
                <img src="{{ asset('img/logo-belovacorp.png')}}" alt="logo" class="logo-dark" style="width: auto; height: 50px;">
            </span>
        </a>
    </div>
    <!--end logo-->
    <div class="menu-content h-100" data-simplebar>
        <ul class="metismenu left-sidenav-menu">
            <li>
                <a href="javascript:void(0);"><i data-feather="settings" class="align-self-center menu-icon"></i><span>Master</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li><a class="nav-link" href="{{ route('akreditasi.index') }}"><i data-feather="layers" class="align-self-center menu-icon"></i>Master BAB</a></li>
                    <li><a class="nav-link" href="{{ url('/akreditasi/bab/1/standars') }}"><i data-feather="grid" class="align-self-center menu-icon"></i>Master Standar</a></li>
                    <li><a class="nav-link" href="{{ url('/akreditasi/standar/1/eps') }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Master EP</a></li>
                </ul>
            </li>

            
            <li>
                <a href="javascript:void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>BAB 1</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li>
                        <a href="javascript:void(0);"><span>Standar 1.1</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/1') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/2') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/3') }}">EP 3</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 1.2</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/4') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/5') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/6') }}">EP 3</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 1.3</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/7') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/8') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/9') }}">EP 3</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/10') }}">EP 4</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/11') }}">EP 5</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/12') }}">EP 6</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/13') }}">EP 7</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/14') }}">EP 8</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/15') }}">EP 9</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/16') }}">EP 10</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 1.4</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/17') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/18') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/19') }}">EP 3</a></li>
                        </ul>
                    </li>
                </ul>
            </li>

            <li>
                <a href="javascript:void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>BAB 2</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li>
                        <a href="javascript:void(0);"><span>Standar 2.1</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/20') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/21') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/22') }}">EP 3</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/23') }}">EP 4</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/24') }}">EP 5</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 2.2</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/25') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/26') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/27') }}">EP 3</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/28') }}">EP 4</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/29') }}">EP 5</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/30') }}">EP 6</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/31') }}">EP 7</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/32') }}">EP 8</a></li>
                            
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 2.3</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/33') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/34') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/35') }}">EP 3</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/36') }}">EP 4</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/37') }}">EP 5</a></li>  
                        </ul>
                    </li>
                </ul>
            </li>
            <li>
                <a href="javascript:void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>BAB 3</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.1</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/38') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/39') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/40') }}">EP 3</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/41') }}">EP 4</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/42') }}">EP 5</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/43') }}">EP 6</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/44') }}">EP 7</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.2</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">  
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/49') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/50') }}">EP 2</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.3</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/45') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/46') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/47') }}">EP 3</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/48') }}">EP 4</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.4</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/51') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/52') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/53') }}">EP 3</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.5</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/54') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/55') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/56') }}">EP 3</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.6</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/57') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/58') }}">EP 2</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.7</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/59') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/60') }}">EP 2</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.8</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/61') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/62') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/63') }}">EP 3</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/64') }}">EP 4</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/65') }}">EP 5</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/66') }}">EP 6</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.9</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/67') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/68') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/69') }}">EP 3</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/70') }}">EP 4</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.10</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/71') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/72') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/73') }}">EP 3</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.11</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/74') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/75') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/76') }}">EP 3</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/77') }}">EP 4</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/78') }}">EP 5</a></li>
                            
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.12</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/86') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/87') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/88') }}">EP 3</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/89') }}">EP 4</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.13</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/79') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/80') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/81') }}">EP 3</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/82') }}">EP 4</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/83') }}">EP 5</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/84') }}">EP 6</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/85') }}">EP 7</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.14</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/90') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/91') }}">EP 2</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 3.15</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/92') }}">EP 1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/93') }}">EP 2</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/94') }}">EP 3</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/95') }}">EP 4</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/96') }}">EP 5</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/97') }}">EP 6</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/98') }}">EP 7</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/99') }}">EP 8</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/100') }}">EP 9</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/101') }}">EP 10</a></li>
                            
                        </ul>
                    </li>

                </ul>
            </li>

        </ul>              
    </div>
</div>
<!-- end left-sidenav-->