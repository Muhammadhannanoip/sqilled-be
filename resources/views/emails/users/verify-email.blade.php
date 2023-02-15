@extends('layouts.app')
@section('content')
<table style="background: #fff; width: 650px; margin: auto; text-align: center;background:#fff;">
         <tr>
            <td>
               <table border="0" cellpadding="0" cellspacing="0" width="525" style="width: 525px; margin: auto; font-size: 14px; font-family: \'MuseoSans-300\';">
                  <tr>
                     <td>
                        <table style="background: #fff; color: #363636; font-size: 16px; padding: 30px 25px 10px; line-height: 24px; text-align: center; width:525px;">
                           <tr>
                              <td>
                                 <h2 style="color: #333; font-family: \'MuseoSans-500\'; padding-bottom: 10px; margin-top: 10px">Verify Your Email
                                 </h2>
                                 <p style="font-size:18px;">Hi {{$user_name}} .</p>
                                 <p>Please verify your Email. Click on the button below.</p>
                                 <p style="text-align:center; padding:20px 0 20px 0;">
                                    <a href="{{$link}}" style="color:#fff;border: none; background-color: #f69a34;border-top: 12px solid #f69a34;border-right: 24px solid #f69a34;border-bottom: 12px solid #f69a34;border-left: 24px solid #f69a34; border-radius: 10px;font-size:18px;text-decoration: none;font-family: \'MuseoSans-100\'">VERIFY EMAIL
                                    </a>
                                 </p>
                                 <p style="font-size:13px; font-style:italic;">or copy this link: <a href="{{$link}}" style="color: #0069aa; text-decoration: none;">{{$link}}</a>
                                 </p>
                              </td>
                           </tr>
                        </table>
                     </td>
                  </tr>
               </table>
            </td>           
         </tr>               
         <tr style="background:#ffffff;" height="30">       
            <td>&nbsp;</td>         
         </tr>      
      </table> 
@endsection


